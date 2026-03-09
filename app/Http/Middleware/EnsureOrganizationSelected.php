<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures authenticated user has a valid active organization (DB: User::current_organization_id).
 * Impersonation expiry is handled globally by ImpersonationExpiry middleware.
 * - 0 organizations → redirect to organization creation.
 * - ≥1 organizations and no current org in DB → redirect to organization selection.
 */
class EnsureOrganizationSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();
        $orgs = $user->organizations()->count();

        // Allow organization list/create/switch without redirect
        if ($request->routeIs('organizations.*')) {
            return $next($request);
        }

        if ($orgs === 0) {
            return redirect()->route('organizations.create');
        }

        $current = $user->currentOrganization;
        if ($current === null) {
            return redirect()->route('organizations.index');
        }

        if ($current->is_suspended) {
            return redirect()->route('organizations.index')->with('error', __('This organization is suspended.'));
        }

        return $next($request);
    }
}
