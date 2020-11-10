<?php

namespace ROTGP\RestEasy\Test\Controllers;

class StreamingServiceController extends BaseController
{
    protected function explicitPermissions($authUser)
    {
        return false;
    }
}
