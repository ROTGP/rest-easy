<?php

namespace ROTGP\RestEasy\Test\Controllers;

use Illuminate\Support\Collection;
use ROTGP\RestEasy\Test\ErrorCodes;

class ArtistController extends BaseController
{
    protected function allowBatch($authUser, $action, $count)
    {
        if ($authUser->id === 6)
            return false;
        
        if ($authUser->id === 7)
            return ErrorCodes::AUTH_USER_DENIED_BATCH_PROCESSING;
        
        if ($authUser->id === 8)
            return 'Auth user 8 may not process batches';

        return true;
    }

    protected function useBatchKeys()
    {
        return $this->authUser()->id !== 2;
    }

    public function willList($queryBuilder)
    {
        event('resteasy.artist.list.will', ['willList', $queryBuilder->toSql()]);
    }

    public function willGet($model)
    {
        $this->updateHistory($model);
    }

    public function willGetBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function willUpdate($model)
    {
        $this->updateHistory($model);
    }

    public function willUpdateBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function willCreate($model)
    {
        $this->updateHistory($model);
    }

    public function willCreateBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function willDelete($model)
    {
        event('resteasy.artist.delete', ['willDelete', $model]);
    }

    public function willDeleteBatch($collection)
    {
        event('resteasy.artist.delete', ['willDeleteBatch', $collection]);
    }
    
    // DID
    
    public function didList($collection)
    {
        event('resteasy.artist.list.did', ['didList', $collection]);
    }

    public function didGet($model)
    {
        $this->updateHistory($model);
    }

    public function didGetBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function didUpdate($model)
    {
        $this->updateHistory($model);
    }

    public function didUpdateBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function didCreate($model)
    {
        $this->updateHistory($model);
    }

    public function didCreateBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function didDelete($model)
    {
        event('resteasy.artist.delete', ['didDelete', $model]);
    }

    public function didDeleteBatch($collection)
    {
        event('resteasy.artist.delete', ['didDeleteBatch', $collection]);
    }

    // AFTER

    public function useAfterHooks()
    {
        return optional($this->authUser())->id !== 9;
    }

    public function afterList($collection)
    {
        event('resteasy.artist.list.after', ['afterList', $collection]);
    }

    public function afterGet($model)
    {
        $this->updateHistory($model);
    }

    public function afterGetBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function afterUpdate($model)
    {
        $this->updateHistory($model);
    }

    public function afterUpdateBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function afterCreate($model)
    {
        $this->updateHistory($model);
    }

    public function afterCreateBatch($collection)
    {
        foreach ($collection as $model)
            $this->updateHistory($model);
    }

    public function afterDelete($model)
    {
        event('resteasy.artist.delete', ['afterDelete', $model]);
    }

    public function afterDeleteBatch($collection)
    {
        event('resteasy.artist.delete', ['afterDeleteBatch', $collection]);
    }

    public function updateHistory($model)
    {
        $value = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];
        $history = $model->history;
        $pieces = array_filter(explode('.', $history));
        $pieces[] = $value;
        $model->history = implode('.', $pieces);
        $model->update();
    }
}
