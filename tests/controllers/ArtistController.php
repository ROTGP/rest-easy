<?php

namespace ROTGP\RestEasy\Test\Controllers;

use Illuminate\Support\Collection;

class ArtistController extends BaseController
{
    public function willGet($model)
    {
        $this->updateHistory($model, 'willGet');
    }

    public function willList($query)
    {
        event('resteasy.artist.list', ['willList', $query->toSql()]);
    }

    public function willUpdate($model)
    {
        $this->updateHistory($model, 'willUpdate');
    }

    public function willCreate($model)
    {
        $this->updateHistory($model, 'willCreate');
    }

    public function willDelete($model)
    {
        event('resteasy.artist.delete', ['willDelete', $model]);
    }

    public function didGet($model)
    {
        $this->updateHistory($model, 'didGet');
    }

    public function didUpdate($model)
    {
        $this->updateHistory($model, 'didUpdate');
    }

    public function didCreate($model)
    {
        $this->updateHistory($model, 'didCreate');
    }

    public function didDelete($model)
    {
        event('resteasy.artist.delete', ['didDelete', $model]);
    }

    public function useAfterHooks()
    {
        return true;
    }

    public function didGetAfter($model)
    {
        $this->updateHistory($model, 'didGetAfter');
    }

    public function didUpdateAfter($model)
    {
        $this->updateHistory($model, 'didUpdateAfter');
    }

    public function didCreateAfter($model)
    {
        $this->updateHistory($model, 'didCreateAfter');
    }

    public function didDeleteAfter($model)
    {
        event('resteasy.artist.delete', ['didDeleteAfter', $model]);
    }

    public function updateHistory($model, $value)
    {
        $history = $model->history;
        $pieces = array_filter(explode('.', $history));
        $pieces[] = $value;
        $model->history = implode('.', $pieces);
        $model->save();
    }
}
