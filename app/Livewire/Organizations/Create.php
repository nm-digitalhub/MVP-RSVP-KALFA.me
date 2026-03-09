<?php

declare(strict_types=1);

namespace App\Livewire\Organizations;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function save(): mixed
    {
        $this->validate();

        $organization = Organization::create([
            'name' => $this->name,
            'slug' => \Illuminate\Support\Str::slug($this->name),
        ]);

        auth()->user()->organizations()->attach($organization->id, [
            'role' => OrganizationUserRole::Owner,
        ]);

        auth()->user()->update(['current_organization_id' => $organization->id]);
        app(\App\Services\OrganizationContext::class)->set($organization);

        try {
            \Illuminate\Support\Facades\Mail::to(auth()->user()->email)->send(new \App\Mail\WelcomeOrganizer($organization, auth()->user()));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send welcome email', ['error' => $e->getMessage()]);
        }

        return $this->redirect(route('dashboard'), navigate: true)
            ->with('status', __('Organization created.'));
    }

    public function render(): View
    {
        return view('livewire.organizations.create');
    }
}
