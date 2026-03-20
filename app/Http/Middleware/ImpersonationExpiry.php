<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global impersonation auto-expiry.
 * Runs on every web request so timeout is enforced regardless of route.
 * If expired: restore original org, clear session keys, redirect to system.dashboard.
 */
class ImpersonationExpiry
{
    public const MAX_MINUTES = 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        if (! $request->session()->has('impersonation.original_organization_id') || ! $request->session()->has('impersonation.started_at')) {
            return $next($request);
        }

        $startedAt = (int) $request->session()->get('impersonation.started_at');
        if (! $startedAt || (now()->timestamp - $startedAt) <= self::MAX_MINUTES * 60) {
            return $next($request);
        }

        $user = $request->user();
        $originalOrgId = $request->session()->pull('impersonation.original_organization_id');
        $request->session()->forget(['impersonation.started_at', 'impersonation.original_admin_id']);
        $user->update(['current_organization_id' => $originalOrgId]);
        if ($originalOrgId !== null) {
            $request->session()->put('active_organization_id', $originalOrgId);
        } else {
            $request->session()->forget('active_organization_id');
        }

        \App\Services\SystemAuditLogger::log(
            actor: $user,
            action: 'impersonation.ended',
            target: null,
            metadata: [
                'expired' => true,
                'restored_organization_id' => $originalOrgId,
                'duration_minutes' => (int) round((now()->timestamp - $startedAt) / 60),
            ],
        );

        return redirect()->route('system.dashboard')->with('message', __('Impersonation session expired.'));
    }
}
