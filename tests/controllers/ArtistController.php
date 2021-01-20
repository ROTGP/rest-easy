<?php

namespace ROTGP\RestEasy\Test\Controllers;

class ArtistController extends BaseController
{
    public function beforeGet($model)
    {
        event('hook.artist', 'getHook');
    }

    public function beforeList($query)
    {
        event('hook.artist', 'listHook');
    }

    public function beforeUpdate($model)
    {
        event('hook.artist', 'updateHook');
    }

    public function beforeCreate($model)
    {
        event('hook.artist', 'createHook');
    }

    public function beforeDelete($model)
    {
        event('hook.artist', 'deleteHook');
    }
}
