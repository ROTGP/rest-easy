<?php

namespace ROTGP\RestEasy\Test\Controllers;

class PlayController extends BaseController
{
    
    protected function authUser()
    {
        // dd(request()->fullUrl());
        // dd('auth user is....', auth()->user());
        // dd('auth user is....', auth()->user()->toArray());
        return auth()->user();
    }
}
