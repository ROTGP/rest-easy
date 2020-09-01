<?php

namespace ROTGP\RestEasy\Traits;

use Event;
use Closure;
use Exception;
use Str;
use Illuminate\Http\Request;
use Log;
use ReflectionClass;
use App;
use Carbon\Carbon;

trait EloquentGuardTrait
{      
    protected $debugEvents = [];
    protected $eventNames = [
        'retrieved',
        'creating',
        'created',
        'updating',
        'updated',
        'saving',
        'saved',
        'deleting',
        'deleted',
        'restoring',
        'restored',
        'forceDeleted',
        'attaching',
        'attached',
        'detaching',
        'detached',
    ];

    protected $eventActions = [
        'retrieved' => 'read',
        'creating' => 'create',
        'updating' => 'update',
        'deleting' => 'delete',
        'attaching' => 'attach',
        'detaching' => 'detach'
    ];

    protected function startEloquentGuard() : void
    {
        if ($this->guardModels() !== true)
            return;
        Event::listen('eloquent.*', Closure::fromCallable([$this, 'onModelEvent']));
    }

    /**
     * If true, eloquent model events will be listened
     * to automatically. Models may implement hooks for
     * will/did read/create/update/delete/attach/detach
     * events. Additionally, permissions may be granted
     * for each event, using the can{action} event. If
     * explicitPermissions is false, then permission methods
     * MUST be implemented, otherwise the permission 
     * will be interpreted as false.
     *
     * @return bool
     */
    protected function guardModels() : bool
    {
        return true;
    }

    /**
     * If true, each permission method must be defined 
     * explicitly on the model, and the absence of the
     * method will be interpreted as a denial.
     *
     * @return bool
     */
    protected function explicitPermissions() : bool
    {
        return true;
    }

    protected function onModelEvent($eventName, array $data) : void
    {      
        $event = trim(strstr(strstr($eventName, '.'), ':', true), '.:');
        if (!in_array($event, $this->eventNames))
            return;
        $model = $data[0];
        $secondaryModel = $data[1] ?? null;
        $this->debugEvents[] = [$event, $model];

        foreach ($this->eventNames as $name)
            $debugHookNames[] = $this->getHookName($name);
        // dd($debugHookNames);

        $hookName = $this->getHookName($event);
        if (method_exists($model, $hookName))
            $model->{$hookName}();
        if (method_exists($this, $hookName))
            $this->{$hookName}($model);
        
        $permissionMethodName = $this->getPermissionName($event);
        if ($permissionMethodName === null)
            return;
        $explicitPermissions = $this->explicitPermissions();
        // if permission method not defined, and we're not using explicit permissions
        $permissionMethodExists = method_exists($model, $permissionMethodName);
        if (!$permissionMethodExists && !$explicitPermissions)
            return;

        $allowed = false;
        if ($permissionMethodExists) {
            $authUser = $this->getAuthUser();
            $params = [$authUser];
            if ($secondaryModel !== null)
                array_unshift($params, $secondaryModel);
            $allowed = $this->callProtectedMethod(
                $model,
                $permissionMethodName,
                ...$params
            );
        }
        
        if ($allowed === true)
            return;
    
        if ($allowed === false)
            $allowed = null;
        
        $permissionException = $this->buildPermissionException(
            $allowed,
            $model,
            $secondaryModel,
            $event
        );
        $this->logPermissionViolation($permissionException);
        $this->errorResponse($allowed, 403);
    }

    protected function buildPermissionException($errorCode, $model, $secondaryModel, $event) : array
    {
        $data = [];
        $authUser = $this->getAuthUser();
        $data['request_url'] = strtoupper($this->method) . ' ' . $this->request->url();
        $data['request_time'] = Carbon::now()->toString();
        $data['environment'] = App::environment();
        $data['error_code'] = is_int($errorCode) ? $errorCode : null;
        $errorKey = $this->findErrorKey($errorCode);
        $data['error_message'] = $errorKey ===  null ? null : ucfirst(strtolower(str_replace('_', ' ', $errorKey)));
        $data['auth_user_id'] = $authUser->id;        
        $data['model'] = get_class($model);
        $data['model_id'] = optional($model)->id;
        if ($secondaryModel !== null) {
            $data['secondary_model'] = get_class($secondaryModel);
            $data['secondary_model_id'] = optional($secondaryModel)->id;
        }
        $verb = $this->eventActions[$event];
        $message = $authUser->id === null ? 'Unauthenticated user' : 'Authenticated user with id ' . $authUser->id;
        $message .= ' was denied permission to ' . $verb;
        if ($secondaryModel !== null) {
            $message .= ' model ' . (new ReflectionClass($secondaryModel))->getShortName();
            $message .= ' with id ' . $secondaryModel->id . ' ';
            $syncVerb = $verb === 'attach' ? 'to' : 'from';
            $message .=  $syncVerb . ' model ' . (new ReflectionClass($model))->getShortName();
            $message .= ' with id ' . $model->id;
        } else {
            $message .= ' model ' . (new ReflectionClass($model))->getShortName();
            if ($model->id !== null) $message .= ' with id ' . $model->id;
        }

        $data['message'] = $message;
        return $data;
    }

    protected function logPermissionViolation($permissionException) : void
    {
        Log::error($permissionException);
    }

    protected function getPermissionName($event) : ?string
    {
        if (array_key_exists($event, $this->eventActions) === false)
            return null;
        return 'can' . Str::studly($this->eventActions[$event]);
    }

    protected function getHookName($event) : string
    {
        $result = '';
        if (preg_match('/ing$/', $event)) {
            $result = 'will' . ucfirst(substr($event, 0, -3) . 'e');
        } else if (preg_match('/ed$/', $event)) {
            $result = 'did' . ucfirst(substr($event, 0, -1));
        }
        if ($result === '')
            throw new Exception('No hook name found');
        if (preg_match('/ache$/', $result))
            $result = substr($result, 0, -1);
        return $result;
    }
}
