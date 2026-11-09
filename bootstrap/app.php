<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'super' => EnsureSuperAdmin::class,
        ]);
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->authenticateSessions(); // makes "sign out other devices" effective
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        // Behind a proxy / Cloudflare / load balancer, so rate limits use the real client IP:
        // $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
