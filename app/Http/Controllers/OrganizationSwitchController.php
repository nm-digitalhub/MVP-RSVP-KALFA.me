<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class OrganizationSwitchController extends Controller
{
    public function __invoke(Organization $organization): RedirectResponse
    {
        abort_unless(
            auth()->user()->organizations->contains($organization),
            403
        );

        auth()->user()->update([
            'current_organization_id' => $organization->id,
        ]);

        app(\App\Services\OrganizationContext::class)->set($organization);

        return redirect()->route('dashboard');
    }
}
