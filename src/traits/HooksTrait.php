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

    public function didUpdate($model)
    {
        //
    }

    public function didCreate($newModel)
    {
        //
    }

    public function didDelete($model)
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

    public function didUpdateAfter($model)
    {
        //
    }

    public function didCreateAfter($newModel)
    {
        //
    }

    public function didDeleteAfter($model)
    {
        //
    }
}
