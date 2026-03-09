<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\OrganizationInvitation;
use App\Services\OrganizationMemberService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class AcceptInvitation extends Component
{
    public string $token;

    public ?OrganizationInvitation $invitation = null;

    protected OrganizationMemberService $memberService;

    public function boot(OrganizationMemberService $memberService): void
    {
        $this->memberService = $memberService;
    }

    #[Layout('layouts.guest')]
    #[Title('Accept Invitation')]
    public function mount(string $token): void
    {
        $this->token = $token;
        $this->invitation = OrganizationInvitation::where('token', $token)->first();

        if (! $this->invitation || $this->invitation->isExpired()) {
            session()->flash('error', __('This invitation is invalid or has expired.'));
            $this->redirect(route('login'), navigate: true);

            return;
        }

        // If user is already a member, just redirect
        if (auth()->check() && auth()->user()->organizations->contains($this->invitation->organization_id)) {
            session()->flash('message', __('You are already a member of this organization.'));
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function accept(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('register', ['invitation' => $this->token]), navigate: true);

            return;
        }

        try {
            $organization = $this->memberService->acceptInvitation($this->token, auth()->user());

            session()->flash('success', __('You have joined :organization!', ['organization' => $organization->name]));
            $this->redirect(route('dashboard'), navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.accept-invitation');
    }
}
