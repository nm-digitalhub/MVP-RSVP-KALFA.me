<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\SystemAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SystemImpersonationController extends Controller
{
    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('impersonate-users')) {
            abort(403);
        }

        $originalOrgId = $user->current_organization_id;
        $request->session()->put('impersonation.original_admin_id', $user->id);
        $request->session()->put('impersonation.original_organization_id', $originalOrgId);
        $request->session()->put('impersonation.started_at', now()->timestamp);

        $user->update(['current_organization_id' => $organization->id]);
        $request->session()->put('active_organization_id', $organization->id);

        SystemAuditLogger::log(
            actor: $user,
            action: 'impersonation.started',
            target: $organization,
            metadata: ['organization_id' => $organization->id, 'organization_name' => $organization->name],
        );

        return redirect()->route('dashboard');
    }
}
