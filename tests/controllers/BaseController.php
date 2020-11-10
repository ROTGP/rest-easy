<?php

namespace ROTGP\RestEasy\Test\Controllers;

use Illuminate\Routing\Controller;

use ROTGP\RestEasy\RestEasyTrait;

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
