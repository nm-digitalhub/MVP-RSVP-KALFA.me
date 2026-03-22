<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates tenant feature routes behind an active account (product or subscription).
 *
 * Works for both web (session org) and API (route-param org) contexts:
 *  - Web: resolves org from $user->currentOrganization; redirects to billing.account on failure.
 *  - API: resolves org from the {organization} route parameter; returns HTTP 402 JSON on failure.
 *
 * Passes through:
 *  - System admins (global authority)
 *  - Impersonating admins (session key present)
 *  - Accounts with at least one active AccountProduct
 *  - Accounts with at least one active Subscription (status=active, not ended)
 *  - Accounts on an active Trial (status=trial, trial_ends_at > now)
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // System admins are never blocked by billing gate.
        if ($user->is_system_admin) {
            return $next($request);
        }

        // Admin impersonating an org bypasses billing gate.
        if ($request->session()->has('impersonation.original_organization_id')) {
            return $next($request);
        }

        // Resolve organization from the most specific route context first.
        $routeOrg = $request->route('organization');
        $routeEvent = $request->route('event');

        $org = match (true) {
            $routeOrg instanceof Organization => $routeOrg,
            $routeEvent instanceof Event => $routeEvent->organization,
            default => $user->currentOrganization,
        };

        if ($org === null) {
            // Web: EnsureOrganizationSelected handles redirect. API: pass through.
            return $next($request);
        }

        $account = $org->account;

        if ($account === null) {
            return $this->deny(
                $request,
                __('Your organization is not linked to an account. Please contact support.')
            );
        }

        if ($account->hasBillingAccess()) {
            return $next($request);
        }

        return $this->deny(
            $request,
            __('Your account does not have an active plan. Please choose a plan to continue.')
        );
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'reason' => 'no_active_plan',
            ], 402);
        }

        return redirect()->route('billing.account')
            ->with('warning', $message);
    }
}
