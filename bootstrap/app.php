<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureOrganizationSelected;
use App\Http\Middleware\EnsureSystemAdmin;
use App\Http\Middleware\ImpersonationExpiry;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\RequireImpersonationForSystemAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SpatiePermissionTeam;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\TokenMismatchException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust reverse proxy (Nginx) headers for correct HTTPS/scheme detection
        $middleware->trustProxies(at: '*');

        // Security headers - applied globally (only for HTML responses)
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'ensure.account_active' => EnsureAccountActive::class,
            'ensure.organization' => EnsureOrganizationSelected::class,
            'system.admin' => EnsureSystemAdmin::class,
            'require.impersonation' => RequireImpersonationForSystemAdmin::class,
        ]);
        $middleware->web(replace: [
            ValidateCsrfToken::class => VerifyCsrfToken::class,
        ]);
        $middleware->web(append: [
            RequestId::class,
            ImpersonationExpiry::class,
            SpatiePermissionTeam::class,
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
