<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\Model;

use Schema;
use ReflectionClass;
use ReflectionMethod;
use Exception;
use Str;

trait MetaTrait
{      
    protected $modelMethods;
    protected $columns;

    protected function authUser()
    {
        return auth()->user();
    }

    final protected function getAuthUser()
    {
        return optional($this->authUser);
    }
    
    protected function errorCodes()
    {
        //
    }

    protected function modelNamespace()
    {
        //
    }

    /**
     * Returns a the fillable fields of the model,
     * excluding $immutable fields when the model
     * is being updated.
     *
     * @return array
     */
    protected function getFillable($queriedModel = null) : array
    {
        $fillable = $this->model->getFillable() ?? [];
        $immutableFields = $this->callProtectedMethod(
            $this->queriedModel(),
            'immutableFields',
            $this->getAuthUser()
        ) ?? [];
        return $this->method === 'put' ? 
            array_diff(
                $fillable,
                $immutableFields,
            ) : $fillable;
    }

    public function getColumns() : array
    {
        if ($this->columns === null)
            $this->columns = Schema::getColumnListing($this->model->getTable());
        return $this->columns;
    }

    protected function accessProtectedProperty($prop, $default = [])
    {
        $obj = $this->model;
        $reflection = new ReflectionClass($obj);
        if (!$reflection->hasProperty($prop))
            return $default;
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj) ?? [];
    }

    protected function queriedModel() : Model
    {
        return $this->queriedModel ?? $this->model;
    }

    protected function callProtectedMethod(Model $model, string $methodName, ...$params)
    {
        $reflection = new ReflectionClass($model);
        if (!$reflection->hasMethod($methodName))
            return null;
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($model, ...$params);
    }

    /**
     * Determines and returns the model associated with the
     * controller. If the model can not be determined, an 
     * exception is thrown. Model determination happens in
     * the following order:
     * 
     *  - if the model has already been set, then return it
     *  - if $this->modelNamespace() has been set on the
     *  - controller, and the class explicitly exists, then
     *    return it
     *  - get the model name ($singularized) from the controller
     *  - if $this->modelNamespace() + singularized exists, then 
     *    return it
     *  - otherwise, get the app namespace, and check for existence
     *    of: ns + singularized, ns / model / singularized, or
     *    ns + models / singularized
     *
     * @return Illuminate\Database\Eloquent\Model;
     */
    protected function instantiateModel() : Model
    {
        $namespace = $this->modelNamespace() ?? false;
        if (class_exists($namespace)) {
            $this->model = app($namespace);
            return $this->model;
        }
       
        $modelName = (new ReflectionClass($this))->getShortName();
        $singularized = Str::singular(
            str_replace('Controller', '', $modelName)
        );

        if ($namespace !== false) {
            $candidate = $namespace . '\\' . $singularized;
            if (class_exists($candidate)) {
                $this->model = app($candidate);
                return $this->model;
            }
        }

        $appNS = app()->getNamespace();
        $model = null;
        $candidates = ['', 'Model\\', 'Models\\'];
        foreach ($candidates as $candidate) {
            $candidate = $appNS . $candidate . $singularized;
            if (class_exists($candidate))
                $model = $candidate;
        }
        if ($model === null)
            throw new Exception('Unable to determine model');
        return app($model);
    }

    /**
     * Returns a list of function names that are both
     * public, and belong to either BaseModel, or a 
     * class that extends it.
     *
     * @return array
     */
    protected function getModelMethods() : array
    {  
        if ($this->modelMethods !== null)
            return $this->modelMethods;
        $class = new ReflectionClass($this->model);
        $allMethods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        $methods = [];
        foreach ($allMethods as $method) {
            $methodClassNamespace = substr($method->class, 0, strrpos($method->class, '\\'));
            if ($class->getNamespaceName() === $methodClassNamespace)
                $methods[] = $method->name;
        }
        $this->modelMethods = $methods;
        return $methods;
    }
}
