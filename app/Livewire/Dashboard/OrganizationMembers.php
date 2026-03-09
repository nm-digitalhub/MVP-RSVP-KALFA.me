<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationMemberService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class OrganizationMembers extends Component
{
    public Organization $organization;

    // Invitation form
    public string $email = '';

    public string $role = 'member';

    protected OrganizationMemberService $memberService;

    public function boot(OrganizationMemberService $memberService): void
    {
        $this->memberService = $memberService;
    }

    #[Layout('layouts.app')]
    #[Title('Team Management')]
    public function mount(): void
    {
        $this->organization = auth()->user()->currentOrganization;

        abort_unless($this->organization && Gate::allows('update', $this->organization), 403);
    }

    public function inviteMember(): void
    {
        $this->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,member',
        ]);

        try {
            $this->memberService->invite(
                $this->organization,
                $this->email,
                OrganizationUserRole::from($this->role)
            );

            session()->flash('success', __('Invitation sent to :email', ['email' => $this->email]));
            $this->reset(['email', 'role']);
        } catch (\Exception $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function cancelInvitation(int $invitationId): void
    {
        $invitation = $this->organization->invitations()->findOrFail($invitationId);
        $invitation->delete();

        session()->flash('success', __('Invitation cancelled.'));
    }

    public function removeMember(int $userId): void
    {
        $user = User::findOrFail($userId);

        try {
            $this->memberService->removeMember($this->organization, $user);
            session()->flash('success', __('Member removed.'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function updateRole(int $userId, string $role): void
    {
        $user = User::findOrFail($userId);

        $this->memberService->updateRole(
            $this->organization,
            $user,
            OrganizationUserRole::from($role)
        );

        session()->flash('success', __('Role updated.'));
    }

    public function render(): View
    {
        return view('livewire.dashboard.organization-members', [
            'members' => $this->organization->users()->withPivot('role')->orderBy('name')->get(),
            'invitations' => $this->organization->invitations()->latest()->get(),
        ]);
    }
}
