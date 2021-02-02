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

    public function didGetMany($collection)
    {
        event('hook.artist.did', ['didGetManyHook', $collection]);
    }

    public function didUpdate($model)
    {
        event('hook.artist.did', ['didUpdateHook', $model]);
    }

    public function didUpdateMany($collection)
    {
        event('hook.artist.did', ['didUpdateManyHook', $collection]);
    }

    public function didCreate($model)
    {
        event('hook.artist.did', ['didCreateHook', $model]);
    }

    public function didCreateMany($collection)
    {
        event('hook.artist.did', ['didCreateManyHook', $collection]);
    }

    public function didDelete($model)
    {
        event('hook.artist.did', ['didDeleteHook', $model]);
    }

    public function didDeleteMany($collection)
    {
        event('hook.artist.did', ['didDeleteManyHook', $collection]);
    }

    public function useAfterHooks()
    {
        return true;
    }
}
