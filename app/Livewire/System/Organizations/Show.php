<?php

declare(strict_types=1);

namespace App\Livewire\System\Organizations;

use App\Enums\EventStatus;
use App\Enums\OrganizationUserRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationMemberService;
use App\Services\SystemAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Organization $organization;

    public string $activeTab = 'team';

    public ?string $pendingAction = null;

    public ?int $pendingUserId = null;

    public ?int $pendingEventId = null;

    public string $confirmPassword = '';

    #[Layout('layouts.app')]
    #[Title('Organization')]
    public ?int $directAddUserId = null;

    public string $directAddRole = 'member';

    protected OrganizationMemberService $memberService;

    public function boot(OrganizationMemberService $memberService): void
    {
        $this->memberService = $memberService;
    }

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function addMemberDirect(): void
    {
        $this->validate([
            'directAddUserId' => 'required|exists:users,id',
            'directAddRole' => 'required|in:admin,member',
        ]);

        $user = User::find($this->directAddUserId);
        $this->memberService->addMember($this->organization, $user, OrganizationUserRole::from($this->directAddRole));

        SystemAuditLogger::log(
            actor: auth()->user(),
            action: 'organization.member_added_direct',
            target: $this->organization,
            metadata: ['user_id' => $user->id, 'role' => $this->directAddRole],
        );

        $this->reset(['directAddUserId', 'directAddRole']);
        session()->flash('success', __('Member added successfully.'));
    }

    public function removeMemberDirect(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->memberService->removeMember($this->organization, $user);

        SystemAuditLogger::log(
            actor: auth()->user(),
            action: 'organization.member_removed_direct',
            target: $this->organization,
            metadata: ['user_id' => $userId],
        );

        session()->flash('success', __('Member removed successfully.'));
    }

    public function requestAction(string $action, ?int $userId = null, ?int $eventId = null): void
    {
        $this->pendingAction = $action;
        $this->pendingUserId = $userId;
        $this->pendingEventId = $eventId;
        $this->confirmPassword = '';
        $this->resetValidation();
    }

    public function cancelConfirm(): void
    {
        $this->pendingAction = null;
        $this->pendingUserId = null;
        $this->pendingEventId = null;
        $this->confirmPassword = '';
    }

    public function confirmAndExecute(): void
    {
        $this->validate(['confirmPassword' => 'required|string']);
        if (! Hash::check($this->confirmPassword, auth()->user()->getAuthPassword())) {
            $this->addError('confirmPassword', __('The provided password is incorrect.'));

            return;
        }
        $action = $this->pendingAction;
        $userId = $this->pendingUserId;
        $eventId = $this->pendingEventId;
        $this->pendingAction = null;
        $this->pendingUserId = null;
        $this->pendingEventId = null;
        $this->confirmPassword = '';
        if ($action === 'transferOwnership' && $userId) {
            $this->executeTransferOwnership($userId);

            return;
        }
        if ($action === 'setEventActive' && $eventId) {
            $this->setEventActive($eventId);

            return;
        }
        match ($action) {
            'suspend' => $this->suspend(),
            'activate' => $this->activate(),
            'forceDelete' => $this->forceDelete(),
            'resetData' => $this->resetData(),
            default => null,
        };
    }

    protected function executeTransferOwnership(int $userId): void
    {
        $newOwner = User::find($userId);
        if (! $newOwner || ! $this->organization->users()->where('user_id', $newOwner->id)->exists()) {
            return;
        }
        $currentOwner = $this->organization->owner();
        if ($currentOwner) {
            $this->organization->users()->updateExistingPivot($currentOwner->id, ['role' => OrganizationUserRole::Admin->value]);
        }
        $this->organization->users()->updateExistingPivot($newOwner->id, ['role' => OrganizationUserRole::Owner->value]);
        SystemAuditLogger::log(
            actor: auth()->user(),
            action: 'organization.ownership_transferred',
            target: $this->organization,
            metadata: ['new_owner_id' => $newOwner->id, 'new_owner_email' => $newOwner->email],
        );
        $this->organization->refresh();
    }

    protected function suspend(): void
    {
        $this->organization->update(['is_suspended' => true]);
        SystemAuditLogger::log(auth()->user(), 'organization.suspended', $this->organization, []);
    }

    protected function activate(): void
    {
        $this->organization->update(['is_suspended' => false]);
        SystemAuditLogger::log(auth()->user(), 'organization.activated', $this->organization, []);
    }

    protected function forceDelete(): void
    {
        $id = $this->organization->id;
        $name = $this->organization->name;
        try {
            $this->organization->delete();
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'foreign key') || str_contains($e->getMessage(), 'integrity constraint')) {
                SystemAuditLogger::log(auth()->user(), 'organization.force_delete_blocked', $this->organization, ['reason' => 'referential_integrity']);
                session()->flash('error', __('Cannot delete organization: it has related payments or other linked data.'));
                $this->redirect(route('system.organizations.show', $this->organization), navigate: true);

                return;
            }
            throw $e;
        }
        SystemAuditLogger::log(auth()->user(), 'organization.force_deleted', null, ['organization_id' => $id, 'name' => $name]);
        $this->redirect(route('system.organizations.index'), navigate: true);
    }

    protected function resetData(): void
    {
        // Danger zone: placeholder – e.g. soft-delete all events or reset; guarded.
        SystemAuditLogger::log(auth()->user(), 'organization.reset_data_requested', $this->organization, []);
        $this->organization->refresh();
    }

    protected function setEventActive(int $eventId): void
    {
        $event = Event::where('organization_id', $this->organization->id)->find($eventId);
        if (! $event) {
            return;
        }
        $previousStatus = $event->status?->value;
        $event->update(['status' => EventStatus::Active]);
        SystemAuditLogger::log(
            actor: auth()->user(),
            action: 'event.set_active',
            target: $event,
            metadata: ['previous_status' => $previousStatus],
        );
        $this->organization->refresh();
    }

    public function render(): View
    {
        $organization = $this->organization;
        $owner = $organization->owner();
        $membersCount = $organization->users()->count();
        $members = $organization->users()->orderBy('name')->get();
        $events = $organization->events()->latest('event_date')->paginate(10);
        $allUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.system.organizations.show', [
            'owner' => $owner,
            'membersCount' => $membersCount,
            'members' => $members,
            'events' => $events,
            'allUsers' => $allUsers,
        ]);
    }
}
