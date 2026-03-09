<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SpatiePermissionTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            if ($orgId = $user->current_organization_id) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($orgId);
            }
        }

        return $next($request);
    }
}
