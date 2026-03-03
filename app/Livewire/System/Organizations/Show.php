<?php

namespace App\Livewire\System\Organizations;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\SystemAuditLogger;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Organization $organization;

    public ?string $pendingAction = null;

    public ?int $pendingUserId = null;

    public string $confirmPassword = '';

    #[Layout('layouts.app')]
    #[Title('Organization')]
    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function requestAction(string $action, ?int $userId = null): void
    {
        $this->pendingAction = $action;
        $this->pendingUserId = $userId;
        $this->confirmPassword = '';
        $this->resetValidation();
    }

    public function cancelConfirm(): void
    {
        $this->pendingAction = null;
        $this->pendingUserId = null;
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
        $this->pendingAction = null;
        $this->pendingUserId = null;
        $this->confirmPassword = '';
        if ($action === 'transferOwnership' && $userId) {
            $this->executeTransferOwnership($userId);
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

    public function render()
    {
        $organization = $this->organization;
        $owner = $organization->owner();
        $membersCount = $organization->users()->count();
        $events = $organization->events()->latest('event_date')->paginate(10);

        return view('livewire.system.organizations.show', [
            'owner' => $owner,
            'membersCount' => $membersCount,
            'events' => $events,
        ]);
    }
}
