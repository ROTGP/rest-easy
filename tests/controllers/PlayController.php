<?php

namespace ROTGP\RestEasy\Test\Controllers;

class PlayController extends BaseController
{
    protected function guardModels($authUser)
    {
        return false;
    }
}
