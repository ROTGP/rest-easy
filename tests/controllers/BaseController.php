<?php

namespace ROTGP\RestEasy\Test\Controllers;

use Illuminate\Routing\Controller;

use ROTGP\RestEasy\RestEasyTrait;
use ROTGP\RestEasy\Test\ErrorCodes;

abstract class BaseController extends Controller
{
    use RestEasyTrait;

    protected function errorCodes()
    {
        return ErrorCodes::class;
    }

    protected function modelNamespace()
    {
        // dd('????');
        return 'ROTGP\RestEasy\Test\Models';
    }

    // protected function authUser()
    // {
    //     return User::find(request()->header('Auth-User-Id'));
    // }
}
