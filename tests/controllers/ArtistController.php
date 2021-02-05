<?php

namespace ROTGP\RestEasy\Test\Controllers;

class ArtistController extends BaseController
{
    public function willGet($model)
    {
        event('hook.artist.will', 'willGetHook');
    }

    public function willList($query)
    {
        event('hook.artist.will', 'willListHook');
    }

    public function willUpdate($model)
    {
        event('hook.artist.will', 'willUpdateHook');
    }

    public function willCreate($model)
    {
        event('hook.artist.will', 'willCreateHook');
    }

    public function willDelete($model)
    {
        event('hook.artist.will', 'willDeleteHook');
    }

    public function didGet($model)
    {
        event('hook.artist.did', ['didGetHook', $model]);
    }

    public function didUpdate($model)
    {
        event('hook.artist.did', ['didUpdateHook', $model]);
    }

    public function didCreate($model)
    {
        event('hook.artist.did', ['didCreateHook', $model]);
    }

    public function didDelete($model)
    {
        event('hook.artist.did', ['didDeleteHook', $model]);
    }

    public function useAfterHooks()
    {
        return true;
    }

    public function didGetAfter($model)
    {
        $this->incrementBiography($model);
    }

    public function didUpdateAfter($model)
    {
        $this->incrementBiography($model);
    }

    public function didCreateAfter($model)
    {
        $this->incrementBiography($model);
    }

    public function didDeleteAfter($model)
    {
        // $this->incrementBiography($model);
    }

    public function incrementBiography($model)
    {
        $biography = $model->biography;
        $idx = 0;
        if (preg_match('#(\d+)$#', $biography, $matches)) {
            $biography = str_replace($matches[1], '' , $biography);
            $idx = intval($matches[1]);
        }
        $idx++;
        $model->biography = $biography . $idx;
        $model->save();
        // echo ' IDX => ' . $idx . "\n";
    }
}
