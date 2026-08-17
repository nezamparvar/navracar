<?php

use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureContentManagerRole;
use App\Http\Middleware\EnsureSalesRole;
use App\Http\Middleware\AuthenticateMobileCustomer;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->alias([
            'admin.role' => EnsureAdminRole::class,
            'content.role' => EnsureContentManagerRole::class,
            'sales.role' => EnsureSalesRole::class,
            'mobile.auth' => AuthenticateMobileCustomer::class,
            'guest' => RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
