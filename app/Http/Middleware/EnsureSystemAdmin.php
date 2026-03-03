<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdmin
{
    /**
     * Ensure the authenticated user is a system admin. No tenant/org logic.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->is_system_admin) {
            abort(403);
        }

        return $next($request);
    }
}
