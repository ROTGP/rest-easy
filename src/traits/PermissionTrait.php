<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Carbon\Carbon;

use Event;
use Closure;
use Exception;
use Str;
use Log;
use ReflectionClass;
use App;

trait PermissionTrait
{      
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

    protected $listenerAdded = false;

    /**
     * In certain circumstances, we need to perform database
     * queries (such as finding a model which is to be deleted)
     * which trigger permission requests. So, when we interact 
     * with the database directly (via eloquent), we 
     * temporarily set $listening to false, which tells 
     * onModelEvent to ignore the event. 
     *
     * @var boolean
     */ 
    protected $listening = false;

    protected $ignoreModel = [];

    protected function startEloquentGuard() : void
    {
        if ($this->guardModels($this->authUser()) !== true) {
            $this->disableListeningForModelEvents();
            return;
        }
        $this->enableListeningForModelEvents();
        if (!$this->listenerAdded) {
            Event::listen('eloquent.*', Closure::fromCallable([$this, 'onModelEvent']));
            $this->listenerAdded = true;
        }
    }

    protected function enableListeningForModelEvents()
    {
        $this->listeningForModelEvents = true;
    }

    protected function disableListeningForModelEvents()
    {
        $this->listeningForModelEvents = false;
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
    protected function guardModels($authUser)
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
    protected function explicitPermissions($authUser)
    {
        return true;
    }

    protected function modelEvent($event, $model, $secondaryModel)
    {
        //
    }

    protected function onModelEvent($eventName, array $data, $fake = false)
    {
        if ($this->listeningForModelEvents === false)
            return;
        $event = trim(strstr(strstr($eventName, '.'), ':', true), '.:');
        if (!in_array($event, $this->eventNames))
            return;
        $model = $data[0];
        $secondaryModel = $data[1] ?? null;

        $ignore = count($this->ignoreModel) && 
            $this->ignoreModel[0] === get_class($model) && 
            $this->ignoreModel[1] === $model->getKey();

        if (!$fake && !$ignore) {
            $this->disableListeningForModelEvents();
            $this->modelEvent($event, $model, $secondaryModel);
            $this->enableListeningForModelEvents();
        }
        
        $permissionMethodName = $this->getPermissionName($event);
        if ($permissionMethodName === null)
            return;
        $explicitPermissions = $this->explicitPermissions($this->authUser());
        $permissionMethodExists = method_exists($model, $permissionMethodName);
        // if permission method not defined, and we're not using explicit permissions
        if (!$permissionMethodExists && !$explicitPermissions)
            return;

        $allowed = false;
        if ($permissionMethodExists) {
            $authUser = $this->authUser();
            $params = [$authUser];
            if ($secondaryModel !== null)
                array_unshift($params, $secondaryModel);
            // temporarily disable listening as the permission
            // method may make eloquent queries
            $this->disableListeningForModelEvents();
            $allowed = $this->callModelMethod(
                $model,
                $permissionMethodName,
                ...$params
            );
            $this->enableListeningForModelEvents();
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

        return false;
    }

    protected function buildPermissionException($errorCode, $model, $secondaryModel, $event) : array
    {
        $data = [];
        $authUser = $this->authUser();
        $data['request_url'] = strtoupper($this->method) . ' ' . $this->request->url();
        $data['request_time'] = Carbon::now()->toString();
        $data['environment'] = App::environment();
        $data['error_code'] = is_int($errorCode) ? $errorCode : null;
        $errorKey = $this->findErrorKey($errorCode);
        $data['error_message'] = $errorKey ===  null ? null : ucfirst(strtolower(str_replace('_', ' ', $errorKey)));
        $data['auth_user_key'] = $authUser->getKey();
        $data['model'] = get_class($model);
        $data['model_key'] = optional($model)->getKey();
        if ($secondaryModel !== null) {
            $data['secondary_model'] = get_class($secondaryModel);
            $data['secondary_model_key'] = optional($secondaryModel)->getKey();
        }
        $verb = $this->eventActions[$event];
        $message = $authUser->getKey() === null ? 'Unauthenticated user' : 'Authenticated user with key ' . $authUser->getKey();
        $message .= ' was denied permission to ' . $verb;
        
        if ($secondaryModel !== null) {
            $message .= ' model ' . class_basename($secondaryModel);
            $message .= ' with key ' . $secondaryModel->getKey() . ' ';
            $syncVerb = $verb === 'attach' ? 'to' : 'from';
            $message .=  $syncVerb . ' model ' . class_basename($model);
            $message .= ' with key ' . $model->getKey();
        } else {
            $message .= ' model ' . class_basename($model);
            if ($model->getKey() !== null) $message .= ' with key ' . $model->getKey();
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
}
