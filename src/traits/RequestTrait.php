<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

use Symfony\Component\HttpFoundation\Response;

use Str;

trait RequestTrait
{   
    protected function transform(array $payload) : array
    {
        $transformations = $this->getTransformations();
        $modelMethods = $this->getModelMethods();
        foreach ($payload as $field => $value) {
            foreach ($transformations as $transformationField => $transformationRules) {
                if ($field !== $transformationField) continue;
                $transformRules = explode('|', $transformationRules);
                foreach ($transformRules as $transformRule) {
                    $ruleName = 'transform' . Str::studly($transformRule);
                    if (!in_array($ruleName, $modelMethods))
                        continue;
                    $payload[$field] = $this->callProtectedMethod(
                        $this->queriedModel(),
                        $ruleName,
                        $payload[$field]
                    );
                }
            }
        }
        return $payload;
    }

    protected function queryParams() : array
    {
        return $this->request->query();    
    }

    protected function payload($prune = false, $model = null, $payload = null) : array
    {
        if ($payload === null)
            $payload = $this->request->json()->all();

        // when updating a model, fill payload with any fields that
        // aren't provided, thus allowing for PATCH funcionality with
        // PUT requests
        if ($model !== null)
            $payload = array_merge($model->toArray(), $payload);
        if ($prune) $payload = array_intersect_key(
            $payload,
            array_flip($this->getFillable())
        );
        $payload = $this->transform($payload);
        return $payload;
    }
}
