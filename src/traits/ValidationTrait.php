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
        $rules = $this->callModelMethod(
            $model,
            'validationRules',
            ...[$this->authUser(),
            optional($model)->getKey()]
        ) ?? [];
        $modelRules = [];
        foreach ($rules as $key => $value) {
            $parts = explode('|', $value);
            for ($i = 0; $i < sizeof($parts); $i++) {
                if (strtolower($parts[$i]) === 'unique') {
                    $parts[$i] = 'unique:' . get_class($this->model) . ',' . $key;
                    $id = optional($model)->getKey();
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
        $customFieldRules = [];
        $safeRules = [];
        $fillable = $this->getFillable($model);
        foreach ($rules as $field => $fieldRule) {
            $fieldParts = explode('|', $fieldRule);
            for ($i = 0; $i < sizeof($fieldParts); $i++) {
                $subParts = explode(':', $fieldParts[$i]);
                $ruleName = 'validate' . Str::studly($subParts[0]);
                if (method_exists($model, $ruleName) && $field !== 'model' && in_array($field, $fillable)) {
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
            $fullPayload,
            $prunedPayload,
            $model) 
        {
            foreach ($customFieldRules as $rule) {
                $field = $rule['field'];
                $payload = $prunedPayload[$field] ?? null;
                
                $invocationParams = [$field, $payload, $rule['params']];
                $error = $this->callModelMethod(
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
                if (method_exists($model, $ruleName) === false)
                    continue;
                $invocationParams = ['model', optional($fullPayload), $params];
                $error = $this->callModelMethod(
                    $model,
                    $ruleName,
                    ...$invocationParams
                );
                if ($error !== null)
                    $validator->errors()->add('model', $error);
            }
        });

        if ($validator->fails())
            return json_decode(json_encode($this->translateValidationMessages($validator)), true);
    }

    protected function translateValidationMessages($validator)
    {
        return $validator->messages()->messages();
    }

    protected function useBatchKeys()
    {
        return true;
    }
}
