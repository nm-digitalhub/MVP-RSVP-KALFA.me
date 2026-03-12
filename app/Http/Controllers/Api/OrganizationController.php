<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

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
        $this->authorize('view', $organization);

        return response()->json($organization);
    }

    /**
     * Update organization details. Requires Owner or Admin role.
     */
    public function update(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email'],
            'settings' => ['nullable', 'array'],
        ]);

        $organization->update($validated);

        return response()->json($organization);
    }
}
