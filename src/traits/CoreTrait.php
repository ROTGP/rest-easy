<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

use DB;
use ReflectionClass;
use Str;

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

    protected function init(Request $request) {
        $this->request = $request;
        $this->model = $this->instantiateModel();
        $this->authUser = $this->authUser();
        $this->query = $this->model->query();
        $this->method = strtolower($this->request->getMethod());
        $this->modelNamespace = (new ReflectionClass($this->model))->getNamespaceName();
        $this->startEloquentGuard();
    }

    protected function did($verb, $payload)
    {
        $action = '';

        if (is_a($payload, Collection::class) && count($payload) === 1)
            $payload = $payload->first();
        
        if (is_subclass_of($payload, Model::class)) {
            $action = 'did' . $verb;
        } else if (is_a($payload, Collection::class)) {
            // normalize collection type to Illuminate\Support\Collection
            $payload = collect($payload);
            $action = 'did' . $verb . 'Many';
        } else {
            return;
        }
        
        $this->{$action}($payload);
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
    
        $this->did('Get', $response);
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
        $ids = $this->parseIds($resource);
        $responseModels = [];
        foreach ($ids as $id)
            $responseModels[] = $this->getModel($id);
        $isBatch = count($responseModels) > 1;
        $responseModels = $isBatch ? $responseModels : $responseModels[0];
        $isBatch ? $this->didGetMany(collect($responseModels)) : $this->didGet($responseModels);
        $this->did('Get', $responseModels);
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
        $ids = $this->parseIds($resource);
        $payload = $this->request->json()->all();
        $isBatch = !$this->isAssociative($payload);
        $queriedModels = [];
        
        foreach ($ids as $id) {
            if ($isBatch) {
                $queriedModel = $this->findModel($id, true);
                $pl = $this->findBatchPayload($id, $payload);
                if ($pl == null)
                    continue;
                $queriedModel->fill($this->payload(true, null, $pl));
                $queriedModels[] = $queriedModel;
            } else {
                $queriedModel = $this->findModel($id, true);
                $queriedModel->fill($this->payload(true));
                $queriedModels[] = $queriedModel;            
            }
        }
        
        $responseModels = DB::transaction(function () use ($queriedModels, $isBatch) {
            $validationErrorCollection = [];
            $hasValidationErrors = false;
            $result = [];
            foreach ($queriedModels as $queriedModel) {
                $this->willUpdate($queriedModel);
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

                $queriedModel->save();
                $this->disableListening();
                $queriedModel->refresh();
                $this->enableListening();
                if (empty($this->queryParams())) {
                    $result[] = $queriedModel;
                    continue;
                }
                // reset query, in case we're batching
                if ($isBatch)
                    $this->query = $this->model->query();
                $this->applySyncs($queriedModel, $this->getAuthUser());
                
                $this->query->where($this->model->getKeyName(), $queriedModel->getKey());
                $result[] = $this->applyQueryFilters($queriedModel->getKey())->first();
            }

            if ($hasValidationErrors) {
                $validationErrors = $isBatch ? $validationErrorCollection : $validationErrorCollection[0];
                $this->errorResponse(
                    null,
                    Response::HTTP_BAD_REQUEST,
                    ['validation_errors' => $validationErrors]);
            }
            return $result;
        });
        // dd($this->debugEvents);
        // $responseModels = $isBatch ? $responseModels : $responseModels[0];
        $isBatch ? $this->didUpdateMany(collect($responseModels)) : $this->didUpdate($responseModels);
        // $this->did('Update', $responseModels);
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
        $isBatch = !$this->isAssociative($payload);
        $newModels = [];
        if ($isBatch) {
            foreach ($payload as $pl)
                $newModels[] = new $this->model($this->payload(true, null, $pl));
        } else {
            $newModels[] = new $this->model($this->payload(true));
        }
        
        $responseModels = DB::transaction(function () use ($newModels, $isBatch) {
            $validationErrorCollection = [];
            $hasValidationErrors = false;
            $result = [];
            foreach ($newModels as $newModel) {
                $this->willCreate($newModel);
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

                $newModel->save();
                $this->disableListening();
                $newModel->refresh();
                $this->enableListening();
                if (empty($this->queryParams())) {
                    $result[] = $newModel;
                    continue;
                }
                // reset query, in case we're batching
                if ($isBatch)
                    $this->query = $this->model->query();
                $this->applySyncs($newModel, $this->getAuthUser());
                $this->query->where($this->model->getKeyName(), $newModel->getKey());
                $result[] = $this->applyQueryFilters($newModel->id)->first();
            }

            if ($hasValidationErrors) {
                $validationErrors = $isBatch ? $validationErrorCollection : $validationErrorCollection[0];
                $this->errorResponse(
                    null,
                    Response::HTTP_BAD_REQUEST,
                    ['validation_errors' => $validationErrors]);
            }
            return $result;
        });
        $responseModels = $isBatch ? $responseModels : $responseModels[0];
        $isBatch ? $this->didCreateMany(collect($responseModels)) : $this->didCreate($responseModels);
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
        $isBatch = false;
        DB::transaction(function () use ($resource, &$responseModels, &$isBatch) {
            $ids = $this->parseIds($resource);
            $isBatch = sizeof($ids) > 1;
            foreach ($ids as $id) {
                $modelToDelete = $this->findModel($id, true);
                $this->willDelete($modelToDelete);
                $modelToDelete->delete();
                $responseModels[] = $modelToDelete;
            }
        });
        $isBatch ? $this->didDeleteMany(collect($responseModels)) : $this->didDelete($responseModels[0]);
        return $this->successfulResponse(null);
    }
}
