<?php

namespace ROTGP\RestEasy\Test\Controllers;

use ROTGP\RestEasy\Test\ErrorCodes;

class AlbumController extends BaseController
{
    protected function errorCodes()
    {
        return ErrorCodes::class;
    }
}
