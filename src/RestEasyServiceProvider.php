<?php

namespace ROTGP\RestEasy;

use ROTGP\RestEasy\Http\Middleware\RestEasyMiddleware;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

class RestEasyServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router, Kernel $kernel)
    {        
        $kernel->pushMiddleware(RestEasyMiddleware::class);
    }
}