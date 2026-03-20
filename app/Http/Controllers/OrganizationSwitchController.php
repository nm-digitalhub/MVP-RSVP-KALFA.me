<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
