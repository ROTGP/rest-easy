<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

use Symfony\Component\HttpFoundation\Response;

use Str;
use ReflectionClass;
use ReflectionMethod;
use Exception;

trait ValidationTrait
{      
    protected function performValidation($model)
    {
        $rules = $this->callProtectedMethod(
            $model,
            'validationRules',
            ...[$this->getAuthUser(),
            optional($model)->id]
        ) ?? [];
        $modelRules = [];
        foreach ($rules as $key => $value) {
            $parts = explode('|', $value);
            for ($i = 0; $i < sizeof($parts); $i++) {
                if (strtolower($parts[$i]) === 'unique') {
                    // see https://laravel.com/docs/7.x/validation#introduction
                    $parts[$i] = 'unique:' . get_class($this->model) . ',' . $key;
                    // @TODO id should be: $key = optional($model)->getKey();
                    $id = optional($model)->id;
                    if (is_int($id)) $parts[$i] .= ',' . $id;
                } else if (strtolower($parts[$i]) === 'exists') {
                    if (Str::endsWith($key, '_id') === false)
                        continue;
                    $modelClass = $this->modelNamespace . '\\' . Str::studly(substr($key, 0, -3));
                    if (class_exists($modelClass) === false)
                        continue;                    
                    $parts[$i] = 'exists:' . $modelClass . ',id';
                }
            }
            $rules[$key] = implode('|', $parts);
            if (strtolower($key) === 'model') $modelRules = explode('|', $value);
        }

        if (array_key_exists('model', $rules))
            unset($rules['model']);
        
        $prunedPayload = $this->payload(true, $model);
        $fullPayload = $this->payload(false, $model);
        $modelMethods = $this->getModelMethods();
        $customFieldRules = [];
        $safeRules = [];
        $fillable = $this->getFillable($model);
        foreach ($rules as $field => $fieldRule) {
            $fieldParts = explode('|', $fieldRule);
            for ($i = 0; $i < sizeof($fieldParts); $i++) {
                $subParts = explode(':', $fieldParts[$i]);
                $ruleName = 'validate' . Str::studly($subParts[0]);
                if (in_array($ruleName, $modelMethods) && $field !== 'model' && in_array($field, $fillable)) {
                    $customFieldRules[] = [
                        'field' => $field,
                        'rule' => $ruleName,
                        'params' => sizeof($subParts) === 2 ? explode(',', $subParts[1]) : []
                    ];
                    $fieldParts[$i] = null;
                }
            }
            $fieldParts = array_filter($fieldParts);
            $rules[$field] = implode('|', $fieldParts);
        }
        $parsedModelRules = [];
        foreach ($modelRules as $rule) {            
            $parts = explode(':', $rule);
            $parsedModelRules[$parts[0]] = sizeof($parts) === 1 ? [] : explode(',', $parts[1]);
        }
        $modelRules = $parsedModelRules;
        $rules = array_intersect_key($rules, array_flip($this->getFillable()));
        $validator = Validator::make($prunedPayload, $rules);
        $validator->after(function ($validator) use (
            $modelRules,
            $customFieldRules,
            $modelMethods,
            $fullPayload,
            $prunedPayload,
            $model) 
        {
            foreach ($customFieldRules as $rule) {
                $field = $rule['field'];
                // @TODO if the field being validated is immutable, and we're PUTing,
                // then do not perform the validation.
                $payload = $prunedPayload[$field] ?? null;
                
                $invocationParams = [$field, $payload, $rule['params']];
                $error = $this->callProtectedMethod(
                    $model,
                    $rule['rule'],
                    ...$invocationParams
                );
                if ($error !== null)
                    $validator->errors()->add(
                        $field,
                        ucfirst(strtolower(str_replace('_',' ', $error)))
                    );
            }

            foreach ($modelRules as $rule => $params) {
                $ruleName = 'validate' . Str::studly($rule);
                if (in_array($ruleName, $modelMethods) === false)
                    continue;
                $invocationParams = ['model', optional($fullPayload), $params];
                $error = $this->callProtectedMethod(
                    $model,
                    $ruleName,
                    ...$invocationParams
                );
                if ($error !== null)
                    $validator->errors()->add('model', $error);
            }
        });

        if ($validator->fails())
            return $this->translateValidationMessages($validator);

        // if ($validator->fails())
            // $this->errorResponse(
            //     null,
            //     Response::HTTP_BAD_REQUEST,
            //     ['validation_errors' => $this->translateValidationMessages($validator)]);
    }

    protected function translateValidationMessages($validator)
    {
        return $validator->messages()->messages();
    }
}
