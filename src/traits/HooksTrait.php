<?php

namespace ROTGP\RestEasy\Traits;

trait HooksTrait
{ 
    // WILL
    
    public function willList($queryBuilder)
    {
        //
    }

    public function willGet($model)
    {
        //
    }

    public function willGetBatch($collection)
    {
        //
    }

    public function willUpdate($model)
    {
        //
    }

    public function willUpdateBatch($collection)
    {
        //
    }

    public function willCreate($model)
    {
        //
    }

    public function willCreateBatch($collection)
    {
        //
    }

    public function willDelete($model)
    {
        //
    }

    public function willDeleteBatch($collection)
    {
        //
    }
    
    // DID
    
    public function didList($collection)
    {
        //
    }

    public function didGet($model)
    {
        //
    }

    public function didGetBatch($collection)
    {
        //
    }

    public function didUpdate($model)
    {
        //
    }

    public function didUpdateBatch($collection)
    {
        //
    }

    public function didCreate($model)
    {
        //
    }

    public function didCreateBatch($collection)
    {
        //
    }

    public function didDelete($model)
    {
        //
    }

    public function didDeleteBatch($collection)
    {
        //
    }

    // AFTER

    public function useAfterHooks()
    {
        return false;
    }

    public function afterList($collection)
    {
        //
    }

    public function afterGet($model)
    {
        //
    }

    public function afterGetBatch($collection)
    {
        //
    }

    public function afterUpdate($model)
    {
        //
    }

    public function afterUpdateBatch($collection)
    {
        //
    }

    public function afterCreate($model)
    {
        //
    }

    public function afterCreateBatch($collection)
    {
        //
    }

    public function afterDelete($model)
    {
        //
    }

    public function afterDeleteBatch($collection)
    {
        //
    }
}
