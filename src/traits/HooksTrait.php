<?php

namespace ROTGP\RestEasy\Traits;

trait HooksTrait
{  
    public function willList($query)
    {
        //
    }

    public function willGet($model)
    {
        //
    }

    public function willUpdate($model)
    {
        //
    }

    public function willCreate($newModel)
    {
        //
    }

    public function willDelete($model)
    {
        //
    }

    // @TODO add tests for all 'did' hooks
    public function didGet($model)
    {
        //
    }

    public function didGetMany($collection)
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

    public function useAfterHooks()
    {
        return false;
    }

    public function didGetAfter($model)
    {
        //
    }

    public function didGetManyAfter($collection)
    {
        //
    }

    public function didUpdateAfter($model)
    {
        //
    }

    public function didUpdateManyAfter($collection)
    {
        //
    }

    public function didCreateAfter($newModel)
    {
        //
    }

    public function didCreateManyAfter($collection)
    {
        //
    }

    public function didDeleteAfter($model)
    {
        //
    }

    public function didDeleteManyAfter($collection)
    {
        //
    }
}
