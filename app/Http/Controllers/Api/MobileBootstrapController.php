<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobileBootstrapResource;
use App\Models\User;
use Illuminate\Http\Request;

class MobileBootstrapController extends Controller
{
    public function show(Request $request): MobileBootstrapResource
    {
        /** @var User $user */
        $user = $request->user();

        $memberships = $user->organizations()->orderBy('organizations.name')->get();

        return new MobileBootstrapResource([
            'user' => $user,
            'current_organization' => $user->currentOrganization,
            'memberships' => $memberships,
            'abilities' => $user->currentAccessToken()?->abilities ?? [],
            'flags' => [
                'can_use_mobile' => true,
                'has_current_organization' => $user->currentOrganization !== null,
                'requires_organization_selection' => $user->currentOrganization === null && $memberships->isNotEmpty(),
            ],
            'server_time' => now()->utc()->toJSON(),
        ]);
    }
}
