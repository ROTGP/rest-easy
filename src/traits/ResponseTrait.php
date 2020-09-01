<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Exception;
use ReflectionClass;

trait ResponseTrait
{      
    protected function successfulResponse($response) : Response
    {
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

    protected function errorResponse($errorCode = null, int $httpStatusCode, $extras = []) : void
    {
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

    protected function findErrorKey ($errorCode)
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
