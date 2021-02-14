<?php

namespace ROTGP\RestEasy\Traits;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

use Route;
use Exception;
use ReflectionClass;
use Str;

trait ResponseTrait
{      
    protected function cleanUp()
    {
        $this->disableListening();
    }

    protected function successfulResponse($response) : Response
    {
        $this->cleanUp();
        /**
         * Now that we have our response and all user-initiated
         * requests have been resolved, it's safe to disable 
         * model event listening, so that models may be queried
         * in did/after hooks.
         */

        $actionMethod = Route::getCurrentRoute()->getActionMethod();
        $method = Str::studly($this->method);
        
        if ($method === 'Index') {
            $method = 'Get';
        } else if ($method === 'Post') {
            $method = 'Create';
        } else if ($method === 'Put') {
            $method = 'Update';
        }
        $action = 'did' . $method;
        $didCollection = collect($response);
    
        foreach ($didCollection as $model) {
            // models with no key are excluded, as they're likely aggregates
            if ((is_a($model, Model::class) && $model->getKey() == null) || !is_a($model, Model::class)) continue;
            event('resteasy.after', [$action, $model]);
            $this->{$action}($model);
        }
        
        if ($actionMethod !== 'index' && (is_a($response, Collection::class) || 
            is_array($response)) && !$this->isBatch && count($response) === 1) {
            $response = $response[0];
        }

        if ($method === 'Delete')
            return response()->json(null, $this->successfulHttpCode());

        return response()->json($response, $this->successfulHttpCode());
    }

    protected function successfulHttpCode()
    {    
        $method = $this->method;
        if ($method === 'get' || $method === 'put') {
            return 200;
        } else if ($method === 'post') {
            return 201;
        } else if ($method === 'delete') {
            return 204;
        }
        throw new Exception('Unsupported request method');
    }

    protected function validationErrorResponse($models, $validationErrors) : void
    {
        $result = [];
        if ($this->isBatch) {
            if ($this->useBatchKeys()) {
                $keys = [];
                for ($i = 0; $i < count($models); $i++) {
                    $key = $this->batchPayloadKeys[spl_object_hash($models[$i])];
                    if ($key !== null) $keys[] = $key;
                }
                if (count($keys) === 0 || count($keys) !== count($validationErrors)) {
                    $result = $validationErrors;
                } else {
                    for ($i = 0; $i < count($keys); $i++)
                        $result[strval($keys[$i])] = $validationErrors[$i];
                }
            } else {
                $result = $validationErrors;
            }
        } else {
            $result = $validationErrors[0];
        }
        $this->errorResponse(
            null,
            Response::HTTP_BAD_REQUEST,
            ['validation_errors' => $result]);
    }

    protected function errorResponse($errorCode = null, int $httpStatusCode = 500, $extras = []) : void
    {
        $this->cleanUp();
        
        if (!is_int($httpStatusCode))
            throw new Exception('HTTP status code is required');

        if (!array_key_exists($httpStatusCode, Response::$statusTexts))
            throw new Exception('HTTP status code not found');

        $responseData = [
            'http_status_code' => $httpStatusCode,
            'http_status_message' => Response::$statusTexts[$httpStatusCode]
        ];

        if ($errorCode !== null) 
            $responseData['error_code'] = $errorCode;
        $errorKey = $this->findErrorKey($errorCode);
        if ($errorKey !== null)
            $responseData['error_message'] = $this->translateErrorMessage(strtolower($errorKey));
        if (sizeof($extras) > 0)
            $responseData = array_merge($responseData, $extras);
        abort(response()->json($responseData, $httpStatusCode));
    }

    protected function translateErrorMessage($errorKey)
    {
        return ucfirst(strtolower(str_replace('_', ' ', $errorKey)));
    }

    protected function findErrorKey($errorCode)
    {
        if ($errorCode === 0 ||
            !is_int($errorCode) ||
            $this->errorCodes() === null)
            return null;
        
        $errorCodes = new ReflectionClass($this->errorCodes());
        $constants = $errorCodes->getConstants();
        foreach ($constants as $key => $value) {
            if ($errorCode !== $value) continue;
            return $key;
        }
    }
}
