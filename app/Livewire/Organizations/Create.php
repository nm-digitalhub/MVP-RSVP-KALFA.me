<?php

declare(strict_types=1);

namespace App\Livewire\Organizations;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    public function save(): void
    {
        $this->validate();

        $organization = Organization::create([
            'name' => $this->name,
            'slug' => $this->uniqueSlug($this->name),
        ]);

        auth()->user()->organizations()->attach($organization->id, [
            'role' => OrganizationUserRole::Owner,
        ]);

        auth()->user()->update(['current_organization_id' => $organization->id]);
        app(\App\Services\OrganizationContext::class)->set($organization);

        try {
            Mail::to(auth()->user()->email)->send(new \App\Mail\WelcomeOrganizer($organization, auth()->user()));
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome email', ['error' => $e->getMessage()]);
        }

        session()->flash('status', __('Organization created.'));

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.organizations.create');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
