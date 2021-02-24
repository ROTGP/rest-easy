<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;

use DB;
use ReflectionClass;
use Route;

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
    protected $methodAlias;
    protected $request;
    protected $isBatch = false;

    protected function init(Request $request) {
        $this->request = $request;
        $this->model = $this->instantiateModel();
        $this->authUser = $this->authUser();
        $this->query = $this->model->query();
        $this->method = strtolower($this->request->getMethod());
        $this->methodAlias = $this->methodAliases[
            strtolower(Route::getCurrentRoute()->getActionMethod())
        ];
        $this->modelNamespace = (new ReflectionClass($this->model))
            ->getNamespaceName();
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
        $this->isBatch = false;
        $this->will($this->query);
        $response = $this->applyQueryFilters();

        // when listing and no models are returned
        // permission may be denied however it won't
        // be fired - so we simulate a read event
        if (is_a($response, Collection::class) && 
            $response->count() == 0 &&
            $this->guardModels($this->authUser())) 
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
        $queriedModels = [];
        foreach ($keys as $key)
            $queriedModels[] = $this->findModel($key, false);

        $this->isBatch = count($queriedModels) > 1;
        $this->will($queriedModels);
        
        if (!empty($this->queryParams())) {
            $keyName = $this->model->getKeyName();
            for ($i = 0; $i < count($queriedModels); $i++) {
                $queriedModel = $queriedModels[$i];
                $key = $queriedModel->getKey();
                $this->query->where($keyName, $key);
                $this->applySyncs($queriedModel, $this->authUser());
                $queriedModel = $this->applyQueryFilters($key)->first();
                $queriedModels[$i] = $queriedModel;
            }
        }
        return $this->successfulResponse($queriedModels);
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

        $this->will($queriedModels);
        
        $responseModels = DB::transaction(function () use ($queriedModels) {
            $validationErrorCollection = [];
            $hasValidationErrors = false;
            $result = [];
            foreach ($queriedModels as $queriedModel) {
                if ($this->guardModels($this->authUser()))
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
                $this->applySyncs($queriedModel, $this->authUser());
                
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

        $this->will($newModels);
        
        $responseModels = DB::transaction(function () use ($newModels) {
            $validationErrorCollection = [];
            $hasValidationErrors = false;
            $result = [];
            foreach ($newModels as $newModel) {
                if ($this->guardModels($this->authUser()))
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
                $this->applySyncs($newModel, $this->authUser());
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
        $modelsToDelete = [];
        $keys = $this->parseKeys($resource);
        foreach ($keys as $key)
            $modelsToDelete[] = $this->findModel($key, true);

        $this->isBatch = count($modelsToDelete) > 1;

        $this->will($modelsToDelete);

        DB::transaction(function () use (&$modelsToDelete) {
            foreach ($modelsToDelete as $modelToDelete)
                $modelToDelete->delete();
        });
        return $this->successfulResponse($modelsToDelete);
    }
}
