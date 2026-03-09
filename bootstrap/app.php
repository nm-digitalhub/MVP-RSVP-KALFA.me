<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.organization' => \App\Http\Middleware\EnsureOrganizationSelected::class,
            'system.admin' => \App\Http\Middleware\EnsureSystemAdmin::class,
        ]);
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\ImpersonationExpiry::class,
            \App\Http\Middleware\SpatiePermissionTeam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
