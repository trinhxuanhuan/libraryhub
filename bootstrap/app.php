<?php

use App\Http\Middleware\CheckManualAuth;
use App\Http\Middleware\CheckManualGuest;
use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
            'manual.auth' => CheckManualAuth::class,
            'manual.guest' => CheckManualGuest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
