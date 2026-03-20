<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class OrganizationSwitchController extends Controller
{
    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless(
            $request->user()->organizations->contains($organization),
            403
        );

        $request->user()->update([
            'current_organization_id' => $organization->id,
        ]);

        app(\App\Services\OrganizationContext::class)->set($organization);

        return redirect()->route('dashboard');
    }
}
