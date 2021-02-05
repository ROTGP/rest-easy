<?php

namespace ROTGP\RestEasy\Http\Middleware;

use Closure;
use Event;
use Route;
use ROTGP\RestEasy\RestEasyTrait;

class RestEasyMiddleware
{
    private static $controller;
    private static $hookName;
    private static $hookPayload = [];

    public function handle($request, Closure $next)
    {
        self::$controller = Route::getRoutes()->match($request)->getController();
        self::$hookName = null;
        self::$hookPayload = [];

        // https://laravel.com/docs/8.x/helpers#method-class-uses-recursive
        $usingTrait = in_array(RestEasyTrait::class, class_uses_recursive(self::$controller));

        if ($usingTrait && self::$controller->useAfterHooks())
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
        if (self::$hookName == null)
            return;
        foreach (self::$hookPayload as $model)
            self::$controller->{self::$hookName . 'After'}($model);
    }

    private function afterHook(...$value)
    {
        self::$hookName = $value[0];
        self::$hookPayload[] = $value[1];
        return false;
    }
}