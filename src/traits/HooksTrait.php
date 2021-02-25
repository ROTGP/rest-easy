<?php

namespace ROTGP\RestEasy\Traits;

trait HooksTrait
{ 
    // WILL
    
    protected function willList($queryBuilder)
    {
        //
    }

    protected function willGet($model)
    {
        //
    }

    protected function willGetBatch($collection)
    {
        //
    }

    protected function willUpdate($model)
    {
        //
    }

    protected function willUpdateBatch($collection)
    {
        //
    }

    protected function willCreate($model)
    {
        //
    }

    protected function willCreateBatch($collection)
    {
        //
    }

    protected function willDelete($model)
    {
        //
    }

    protected function willDeleteBatch($collection)
    {
        //
    }
    
    // DID
    
    protected function didList($collection)
    {
        //
    }

    protected function didGet($model)
    {
        //
    }

    protected function didGetBatch($collection)
    {
        //
    }

    protected function didUpdate($model)
    {
        //
    }

    protected function didUpdateBatch($collection)
    {
        //
    }

    protected function didCreate($model)
    {
        //
    }

    protected function didCreateBatch($collection)
    {
        //
    }

    protected function didDelete($model)
    {
        //
    }

    protected function didDeleteBatch($collection)
    {
        //
    }

    // AFTER

    final public function _useAfterHooks()
    {
        return $this->useAfterHooks();
    }

    protected function useAfterHooks()
    {
        return false;
    }

    public function _after($action, $payload)
    {
        return $this->{'after' . $action}($payload);
    }

    
    protected function afterList($collection)
    {
        //
    }

    protected function afterGet($model)
    {
        //
    }

    protected function afterGetBatch($collection)
    {
        //
    }

    protected function afterUpdate($model)
    {
        //
    }

    protected function afterUpdateBatch($collection)
    {
        //
    }

    protected function afterCreate($model)
    {
        //
    }

    protected function afterCreateBatch($collection)
    {
        //
    }

    protected function afterDelete($model)
    {
        //
    }

    protected function afterDeleteBatch($collection)
    {
        //
    }
}
