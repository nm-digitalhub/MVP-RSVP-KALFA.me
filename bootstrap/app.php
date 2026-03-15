<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

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
            'require.impersonation' => \App\Http\Middleware\RequireImpersonationForSystemAdmin::class,
        ]);
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\RequestId::class,
            \App\Http\Middleware\ImpersonationExpiry::class,
            \App\Http\Middleware\SpatiePermissionTeam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirect gracefully when CSRF token expires (e.g. idle session on logout form).
        // JSON/XHR requests (Livewire, WebAuthn fetch) get a 419 JSON response instead
        // of a redirect, so the client can handle the error appropriately.
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('CSRF token mismatch.')], 419);
            }

            return redirect()->route('login')
                ->with('status', __('Your session expired. Please log in again.'));
        });
    })->create();
