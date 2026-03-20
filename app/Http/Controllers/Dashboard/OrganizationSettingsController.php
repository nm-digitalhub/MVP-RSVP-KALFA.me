<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateOrganizationSettingRequest;
use App\Services\OrganizationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function __construct(
        private OrganizationContext $context
    ) {}

    public function edit(): View|RedirectResponse
    {
        $organization = $this->context->current();
        if ($organization === null) {
            return redirect()->route('organizations.index');
        }
        Gate::authorize('update', $organization);

        return view('dashboard.organizations.edit', [
            'organization' => $organization,
        ]);
    }

    public function update(UpdateOrganizationSettingRequest $request): RedirectResponse
    {
        $organization = $this->context->current();
        if ($organization === null) {
            return redirect()->route('organizations.index');
        }
        Gate::authorize('update', $organization);

        $validated = $request->validated();

        $organization->update($validated);

        return redirect()->route('organizations.index')
            ->with('success', __('Organization updated.'));
    }
}
