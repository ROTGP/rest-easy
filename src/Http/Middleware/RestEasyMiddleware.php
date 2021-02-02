<?php

namespace ROTGP\RestEasy\Http\Middleware;

use Closure;
use Event;
use Route;
use ROTGP\RestEasy\RestEasyTrait;

class RestEasyMiddleware
{
    private $controller;
    private $hookName;
    private $hookPayload;

    public function handle($request, Closure $next)
    {
        $this->controller = Route::getRoutes()->match($request)->getController();

        // https://laravel.com/docs/8.x/helpers#method-class-uses-recursive
        $usingTrait = in_array(RestEasyTrait::class, class_uses_recursive($this->controller));

        if ($usingTrait && $this->controller->useAfterHooks())
            Event::listen('resteasy.after', Closure::fromCallable([$this, 'afterHook']));
        
        return $next($request);
    }

    /**
     * https://laravel-5.readthedocs.io/en/latest/middleware/#terminable-middleware
     *
     * If you define a terminate method on your middleware, it will automatically be
     * called after the response is sent to the browser.
     */
    public function terminate($request, $response)
    {
        // Store the session data...
        // dd(get_class($request->route()->controller));
        // echo 'y';

        // dd('yeeeah');
    }

    public function afterHook($foo, $bar)
    {
        dd('here!', $foo);
        dd($foo, $bar);
    }
}