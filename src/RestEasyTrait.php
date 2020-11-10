<?php

namespace ROTGP\RestEasy;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

use ROTGP\RestEasy\Traits\ArrayUtilsTrait;
use ROTGP\RestEasy\Traits\PermissionTrait;
use ROTGP\RestEasy\Traits\QueryTrait;
use ROTGP\RestEasy\Traits\ResponseTrait;
use ROTGP\RestEasy\Traits\MetaTrait;
use ROTGP\RestEasy\Traits\ValidationTrait;
use ROTGP\RestEasy\Traits\RequestTrait;
use ROTGP\RestEasy\Traits\SyncTrait;

use DB;
use ReflectionClass;

trait RestEasyTrait
{  
    use ResponseTrait;
    use ArrayUtilsTrait;
    use QueryTrait;
    use PermissionTrait;
    use MetaTrait;
    use ValidationTrait;
    use RequestTrait;
    use SyncTrait;

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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->init($request);
        $response = $this->applyQueryFilters();
        return $this->successfulResponse($response);
    }

    public function beforeList($query)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $resource)
    {
        $this->init($request);
        $ids = $this->parseIds($resource);
        $models = [];
        foreach ($ids as $id)
            $models[] = $this->getModel($id);
        return $this->successfulResponse(
            count($ids) > 1 ? $models : $models[0]
        );
    }

    protected function getModel($id, $returnImmediately = false)
    {
        $queriedModel = $this->findModel($id, false);
        if ($returnImmediately)
            return $queriedModel;
        if (empty($this->queryParams())) {
            $this->beforeGet($queriedModel);
            return $queriedModel;
        }   
        $this->query->where($this->model->getKeyName(), $id);
        $this->applySyncs($queriedModel, $this->getAuthUser());
        $model = $this->applyQueryFilters($id)->first();
        $this->beforeGet($model);
        return $model;
    } 

    public function beforeGet($model)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $resource)
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
                $this->beforeUpdate($queriedModel);
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
        $responseModels = $isBatch ? $responseModels : $responseModels[0];
        return $this->successfulResponse($responseModels);
    }

    /**
     * Update the specified resource in storage.
     */
    public function beforeUpdate($model)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
                $this->beforeCreate($newModel);
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
        return $this->successfulResponse($responseModels);
    }

    public function beforeCreate($newModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $resource)
    {
        $this->init($request);
        DB::transaction(function () use ($resource) {
            $ids = $this->parseIds($resource);
            foreach ($ids as $id) {
                $modelToDelete = $this->findModel($id, true);
                $this->beforeDelete($modelToDelete);
                $modelToDelete->delete();
            }
        });
        return $this->successfulResponse(null);
    }

    public function beforeDelete($model)
    {
        //
    }
}
