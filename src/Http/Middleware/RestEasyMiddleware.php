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
    private static $hookPayload;
    private static $listeningForAfterEvents = false;

    public function handle($request, Closure $next)
    {
        self::$controller = Route::getRoutes()->match($request)->getController();
        self::$hookName = null;
        self::$hookPayload = null;
        self::$listeningForAfterEvents = false;

        // https://laravel.com/docs/8.x/helpers#method-class-uses-recursive
        $usingTrait = in_array(RestEasyTrait::class, class_uses_recursive(self::$controller));
        $useAfterHooks = self::$controller->useAfterHooks();
        self::$listeningForAfterEvents = $useAfterHooks;

        if (self::$listeningForAfterEvents && $usingTrait && $useAfterHooks)
            Event::listen('resteasy.after', Closure::fromCallable([$this, 'afterHook']));
        
        return $next($request);
    }

    /**
     * https://laravel.com/docs/8.x/middleware#terminable-middleware
     *
     * If you define a terminate method on your middleware, it will automatically be
     * called after the response is sent to the browser.
     */
    public function terminate($request, $response)
    {
        if (!self::$listeningForAfterEvents)
            return;
        if (self::$hookName == null)
            return;
        self::$controller->{self::$hookName}(self::$hookPayload);
    }

    private function afterHook(...$value)
    {
        if (!self::$listeningForAfterEvents)
            return;
        self::$hookName = $value[0];
        self::$hookPayload = $value[1];
        return false;
    }
}