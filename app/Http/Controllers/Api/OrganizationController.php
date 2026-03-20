<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Api\UpdateOrganizationRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Get organization details.
     */
    public function show(Request $request, Organization $organization): JsonResponse
    {
        Gate::authorize('view', $organization);

        return response()->json($organization);
    }

    /**
     * Update organization details. Requires Owner or Admin role.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        Gate::authorize('update', $organization);

        $validated = $request->validated();

        $organization->update($validated);

        return response()->json($organization);
    }
}
