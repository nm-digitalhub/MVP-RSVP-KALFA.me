<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind a specific product-engine feature key.
 *
 * Usage in routes:
 *   Route::middleware('ensure.feature:twilio_enabled')
 *   Route::middleware('ensure.feature:voice_rsvp_calls')
 *
 * Resolution order (handled by Gate + AppServiceProvider::boot):
 *   1. Gate::before → system admin → always allowed
 *   2. Gate::before → impersonating admin → always allowed
 *   3. Gate::define('feature') → FeatureResolver → account_entitlements / product_entitlements
 *   4. No entitlement → redirect to /billing?reason=feature:{key}
 *
 * @see \App\Enums\Feature for canonical feature key constants
 */
class EnsureFeatureAccess
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        // System admins bypass the feature gate (full platform access).
        if ($user?->is_system_admin) {
            return $next($request);
        }

        // Impersonating admins bypass the feature gate for the org they are in.
        if ($request->session()->has('impersonation.original_organization_id')) {
            return $next($request);
        }

        if (Gate::allows('feature', $featureKey)) {
            return $next($request);
        }

        $message = __('Your current plan does not include access to this feature. Please upgrade to continue.');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'reason' => 'feature:'.$featureKey,
            ], 403);
        }

        return redirect()->route('billing.account', ['reason' => 'feature:'.$featureKey])
            ->with('warning', $message);
    }
}
