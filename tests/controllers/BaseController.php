<?php

namespace ROTGP\RestEasy\Test\Controllers;

use ROTGP\RestEasy\RestEasyTrait;
use ROTGP\RestEasy\Test\ErrorCodes;

use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use RestEasyTrait;

    protected function errorCodes()
    {
        return ErrorCodes::class;
    }

    protected function modelNamespace()
    {
        return 'ROTGP\RestEasy\Test\Models';
    }
}
