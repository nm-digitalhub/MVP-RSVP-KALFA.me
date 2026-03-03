<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\SystemAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SystemImpersonationExitController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->is_system_admin) {
            abort(403);
        }

        $originalOrgId = $request->session()->pull('impersonation.original_organization_id');
        $impersonatedOrgId = $user->current_organization_id;
        $startedAt = $request->session()->pull('impersonation.started_at');
        $request->session()->forget('impersonation.original_admin_id');

        $durationMinutes = $startedAt ? (int) round((time() - $startedAt) / 60) : null;
        SystemAuditLogger::log(
            actor: $user,
            action: 'impersonation.ended',
            target: null,
            metadata: [
                'impersonated_organization_id' => $impersonatedOrgId,
                'restored_organization_id' => $originalOrgId,
                'duration_minutes' => $durationMinutes,
            ],
        );

        $user->update(['current_organization_id' => $originalOrgId]);
        if ($originalOrgId !== null) {
            Session::put('active_organization_id', $originalOrgId);
        } else {
            Session::forget('active_organization_id');
        }

        return redirect()->route('system.dashboard');
    }
}
