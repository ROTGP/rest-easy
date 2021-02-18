<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;

use DB;
use ReflectionClass;

trait CoreTrait
{  
    use ResponseTrait;
    use ArrayUtilsTrait;
    use QueryTrait;
    use PermissionTrait;
    use MetaTrait;
    use ValidationTrait;
    use RequestTrait;
    use SyncTrait;
    use HooksTrait;

    protected $model;
    protected $authUser;
    protected $query;
    protected $method;
    protected $request;
    protected $isBatch = false;

    protected function init(Request $request) {
        $this->request = $request;
        $this->model = $this->instantiateModel();
        $this->authUser = $this->authUser();
        $this->query = $this->model->query();
        $this->method = strtolower($this->request->getMethod());
        $this->modelNamespace = (new ReflectionClass($this->model))->getNamespaceName();
        $this->startEloquentGuard();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function _index(Request $request)
    {
        $this->init($request);
        $response = $this->applyQueryFilters();

        // when listing and no models are returned
        // permission may be denied however it won't
        // be fired - so we simulate a read event
        if (is_a($response, Collection::class) && 
            $response->count() == 0 &&
            $this->guardModels($this->getAuthUser())) 
                $this->onModelEvent(
                    'eloquent.retrieved: ' . get_class($this->model),
                    [$this->model]
                );

        return $this->successfulResponse($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function _show(Request $request, $resource)
    {
        $this->init($request);
        $keys = $this->parseKeys($resource);
        $responseModels = [];
        foreach ($keys as $key)
            $responseModels[] = $this->getModel($key);
        return $this->successfulResponse($responseModels);
    }

     /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function _update(Request $request, $resource)
    {
        $this->init($request);
        $keys = $this->parseKeys($resource);
        $payload = $this->request->json()->all();
        $this->isBatch = !$this->isAssociative($payload);
        $queriedModels = [];

        foreach ($keys as $key) {
            if ($this->isBatch) {
                $queriedModel = $this->findModel($key, true);
                $pl = $this->findBatchPayload($key, $payload);
                if ($pl == null)
                    continue;
                $queriedModel->fill($this->payload(true, null, $pl));
                $this->updateBatchPayloadKey(true, $queriedModel, $pl);
                $queriedModels[] = $queriedModel;
            } else {
                $queriedModel = $this->findModel($key, true);
                $queriedModel->fill($this->payload(true));
                $queriedModels[] = $queriedModel;
            }
        }
        
        $responseModels = DB::transaction(function () use ($queriedModels) {
            $validationErrorCollection = [];
            $hasValidationErrors = false;
            $result = [];
            foreach ($queriedModels as $queriedModel) {
                if ($this->guardModels($this->getAuthUser()))
                    $this->onModelEvent('eloquent.updating: ' . get_class($queriedModel), [$queriedModel]);
                
                $validationErrors = $this->performValidation($queriedModel);
                if ($validationErrors !== null) {
                    $hasValidationErrors = true;
                    $validationErrorCollection[] = $validationErrors;
                } else {
                    $validationErrorCollection[] = [];
                }

                if ($hasValidationErrors)
                    continue;

                $this->will('Update', $queriedModel);
                $queriedModel->save();
                $this->disableListeningForModelEvents();
                $queriedModel->refresh();
                $this->enableListeningForModelEvents();
                if (empty($this->queryParams())) {
                    $result[] = $queriedModel;
                    continue;
                }
                // reset query, in case we're batching
                if ($this->isBatch)
                    $this->query = $this->model->query();
                $this->applySyncs($queriedModel, $this->getAuthUser());
                
                $this->query->where($this->model->getKeyName(), $queriedModel->getKey());
                $result[] = $this->applyQueryFilters($queriedModel->getKey())->first();
            }

            if ($hasValidationErrors)
                $this->validationErrorResponse($queriedModels, $validationErrorCollection);
            return $result;
        });
        return $this->successfulResponse($responseModels);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function _store(Request $request)
    {   
        $this->init($request);

        $payload = $this->request->json()->all();
        $this->isBatch = !$this->isAssociative($payload);
        $newModels = [];
        if ($this->isBatch) {
            foreach ($payload as $pl) {
                $newModel = new $this->model($this->payload(true, null, $pl));
                $this->updateBatchPayloadKey(false, $newModel, $pl);
                $newModels[] = $newModel;
            }
        } else {
            $newModels[] = new $this->model($this->payload(true));
        }
        
        $responseModels = DB::transaction(function () use ($newModels) {
            $validationErrorCollection = [];
            $hasValidationErrors = false;
            $result = [];
            foreach ($newModels as $newModel) {
                if ($this->guardModels($this->getAuthUser()))
                    $this->onModelEvent('eloquent.creating: ' . get_class($newModel), [$newModel]);
                $validationErrors = $this->performValidation($newModel);
                if ($validationErrors !== null) {
                    $hasValidationErrors = true;
                    $validationErrorCollection[] = $validationErrors;
                } else {
                    $validationErrorCollection[] = [];
                }

                if ($hasValidationErrors)
                    continue;
                
                $this->will('Create', $newModel);
                $newModel->save();
                $this->disableListeningForModelEvents();
                $newModel->refresh();
                $this->enableListeningForModelEvents();
                if (empty($this->queryParams())) {
                    $result[] = $newModel;
                    continue;
                }
                // reset query, in case we're batching
                if ($this->isBatch)
                    $this->query = $this->model->query();
                $this->applySyncs($newModel, $this->getAuthUser());
                $this->query->where($this->model->getKeyName(), $newModel->getKey());
                $result[] = $this->applyQueryFilters($newModel->getKey())->first();
            }

            if ($hasValidationErrors)
                $this->validationErrorResponse($newModels, $validationErrorCollection);
            return $result;
        });
        return $this->successfulResponse($responseModels);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function _destroy(Request $request, $resource)
    {
        $this->init($request);
        $responseModels = [];
        DB::transaction(function () use ($resource, &$responseModels) {
            $keys = $this->parseKeys($resource);
            foreach ($keys as $key) {
                $modelToDelete = $this->findModel($key, true);
                $this->will('Delete', $modelToDelete);
                $modelToDelete->delete();
                $responseModels[] = $modelToDelete;
            }
        });
        return $this->successfulResponse($responseModels);
    }
}
