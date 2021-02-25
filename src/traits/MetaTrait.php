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
    protected $columns;
    protected $queriedModel;
    protected $batchPayloadKeys = [];

    protected function updateBatchPayloadKey($updating, $model, $payload)
    {
        $key = null;
        // if we're updating, the native key (id) will always be in the payload
        if ($updating) {
            $key = strval($payload[$model->getKeyName()]);
        } else {
            $key = $payload['tmp_key'] ?? null;
        }
        $this->batchPayloadKeys[spl_object_hash($model)] = $key;
    }

    protected function authUser()
    {
        return optional(auth()->user());
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
        $immutableFields = $this->getImmutableFields();
        return $this->method === 'put' ? 
            array_diff(
                $fillable,
                $immutableFields,
            ) : $fillable;
    }

    public function getImmutableFields() : array
    {

        return $this->callModelMethod(
            $this->queriedModel(),
            'immutableFields',
            $this->authUser()
        ) ?? [];
    }

    public function getColumns() : array
    {
        if ($this->columns === null)
            $this->columns = Schema::getColumnListing($this->model->getTable());
        return $this->columns;
    }

    protected function will($payload)
    {
        $action = $this->methodAlias;

        if ($action !== 'List') {
            $payload = collect($payload);
            $this->validateBatch($payload);
        }
        if (!$this->isBatch && $action !== 'List') $payload = $payload[0];
        if ($this->isBatch)
            $action .= 'Batch';
        
        $this->disableListeningForModelEvents();
        $this->{'will' . $action}($payload);
        $this->enableListeningForModelEvents();
    }

    protected function findModel($key, bool $disableEvents = false) : Model
    {
        if ($disableEvents)
            $this->disableListeningForModelEvents();
        $model = $this->model->find($key);
        if ($disableEvents)
            $this->enableListeningForModelEvents();
        if ($model === null)
            $this->errorResponse(null, Response::HTTP_NOT_FOUND, ['resource_key' => $key]);
        $this->queriedModel = $model;
        return $this->queriedModel;
    }

    protected function queriedModel() : Model
    {
        return $this->queriedModel ?? $this->model;
    }

    protected function callModelMethod(Model $model, string $methodName, ...$params)
    {
        if (!method_exists($model, $methodName))
            return null;
        return $model->{$methodName}(...$params);
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
        
        $modelName = class_basename($this);
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

    public function findBatchPayload($key, $payload)
    {
        $keyName = $this->model->getKeyName();
        foreach ($payload as $pl)
            if (array_key_exists($keyName, $pl) && $pl[$keyName] == $key)
                return $pl;
    }

    protected function parseKeys($resource)
    {
        $keys = explode(',', $resource);
        for ($i = 0; $i < count($keys); $i++) {
            $key = preg_replace("/[^A-Za-z0-9]/", '', $keys[$i]);
            if (is_numeric($key))
                $key = (int) $key;
            $keys[$i] = $key;
        }
        return $keys;
    }

    protected function validateBatch($collection)
    {
        if (!$this->isBatch)
            return;
        
        $action = strtolower($this->methodAlias);
        $allowBatch = $this->allowBatch(
            $this->authUser(),
            strtolower($action),
            $collection->count()
        );
        if ($allowBatch === true)
            return;

        $errorCode = is_int($allowBatch) ? $allowBatch : null;
        $extras = is_string($allowBatch) ? ['error_message' => $allowBatch] : [];
       
        $this->errorResponse(
            $errorCode,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $extras
        );
    }

    protected function allowBatch($authUser, $action, $count)
    {
        return false;
    }
}
