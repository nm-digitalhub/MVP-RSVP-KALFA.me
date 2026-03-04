<?php

declare(strict_types=1);

namespace App\Livewire\Organizations;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function mount(): mixed
    {
        $user = auth()->user();

        if ($user->is_system_admin) {
            return null;
        }

        $organizations = $user->organizations()->get();

        // No organizations → force create
        if ($organizations->isEmpty()) {
            return $this->redirect(
                route('organizations.create'),
                navigate: true
            );
        }

        // Exactly one organization AND none selected yet → auto-select once
        if (
            $organizations->count() === 1 &&
            ! $user->current_organization_id
        ) {
            $organization = $organizations->first();
            app(\App\Services\OrganizationContext::class)->set($organization);

            return $this->redirect(
                route('dashboard'),
                navigate: true
            );
        }

        // Otherwise: stay on organizations page
        return null;
    }

    public function render(): View
    {
        $organizations = auth()->user()
            ->organizations()
            ->withCount('events')
            ->orderBy('name')
            ->get();

        return view('livewire.organizations.index', [
            'organizations' => $organizations,
        ]);
    }
}
