<?php

namespace ROTGP\RestEasy\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

use Symfony\Component\HttpFoundation\Response;

use Str;

trait RequestTrait
{   
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
        return $payload;
    }
}
