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

    protected Model $model;
    protected ?Model $authUser;
    protected Builder $query;
    protected $method;
    protected Request $request;

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
    public function show(Request $request, int $id)
    {
        $this->init($request);
        $queriedModel = $this->findModel($id);
        if (empty($this->queryParams())) {
            $this->beforeGet($queriedModel);
            return $this->successfulResponse($queriedModel);
        }   
        $this->query->where('id', $id);
        $this->applySyncs($queriedModel, $this->getAuthUser());
        $response = $this->applyQueryFilters($id)->first();
        return $this->successfulResponse($response);
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
    public function update(Request $request, int $id)
    {
        $this->init($request);
        $queriedModel = $this->findModel($id);
        $queriedModel->fill($this->payload(true));
        $this->beforeUpdate($queriedModel);
        $noChanges = $queriedModel->isDirty() === false;
        if ($this->guardModels())
            $this->onModelEvent('eloquent.updating: ' . get_class($queriedModel), [$queriedModel]);
        $this->performValidation($queriedModel);
        $response = DB::transaction(function () use ($queriedModel, $id) {            
            $queriedModel->save();
            $queriedModel->refresh();
            if (empty($this->queryParams()))
                return $queriedModel;
            $this->applySyncs($queriedModel, $this->getAuthUser());
            $this->query->where('id', $id);
            return $this->applyQueryFilters($id)->first();
        });
        // dd($this->debugEvents);
        return $this->successfulResponse($response);
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
        $newModel = new $this->model($this->payload(true));
        $this->beforeCreate($newModel);
        if ($this->guardModels())
            $this->onModelEvent('eloquent.creating: ' . get_class($newModel), [$newModel]);
        $this->performValidation($newModel);
        $response = DB::transaction(function () use ($newModel) {
            $newModel->save();
            $newModel->refresh();
            if (empty($this->queryParams()))
                return $newModel;
            $this->applySyncs($newModel, $this->getAuthUser());
            $this->query->where('id', $newModel->id);
            return $this->applyQueryFilters($newModel->id)->first();
        });
        return $this->successfulResponse($response);
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
    public function destroy(Request $request, int $id)
    {
        $this->init($request);
        $modelToDelete = $this->findModel($id);
        $this->beforeDelete($modelToDelete);
        $modelToDelete->delete();
        return $this->successfulResponse(null);
    }

    public function beforeDelete($model)
    {
        //
    }
}
