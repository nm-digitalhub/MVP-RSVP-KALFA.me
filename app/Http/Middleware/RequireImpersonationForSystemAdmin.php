<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents system admins from accessing tenant-scoped routes
 * without an active impersonation session. Normal users pass through.
 */
class RequireImpersonationForSystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->is_system_admin
            && ! $request->session()->has('impersonation.original_organization_id')
        ) {
            abort(403, 'System admins must impersonate an organization to access tenant resources.');
        }

        return $next($request);
    }
}
