<?php

namespace ROTGP\RestEasy\Test\Controllers;

use ROTGP\RestEasy\RestEasyTrait;

use Illuminate\Http\Request;

class SongController extends BaseController
{
    public function store(Request $request)
    {
        $response = $this->_store($request);
        $payload = $response->getData();
        $payload->foo = 'bar';
        $response->setData($payload);
        return $response;
    }
}