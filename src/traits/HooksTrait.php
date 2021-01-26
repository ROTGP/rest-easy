<?php

namespace ROTGP\RestEasy\Traits;

trait HooksTrait
{  
    public function beforeList($query)
    {
        //
    }

    public function beforeGet($model)
    {
        //
    }

    public function beforeUpdate($model)
    {
        //
    }

    public function beforeCreate($newModel)
    {
        //
    }

    public function beforeDelete($model)
    {
        //
    }

    public function didRead($model)
    {
        //
    }

    public function didReadMany($collection)
    {
        //
    }

    public function didUpdate($model)
    {
        //
    }

    public function didUpdateMany($collection)
    {
        //
    }

    public function didCreate($newModel)
    {
        //
    }

    public function didCreateMany($collection)
    {
        //
    }

    public function didDelete($model)
    {
        //
    }

    public function didDeleteMany($collection)
    {
        //
    }
}
