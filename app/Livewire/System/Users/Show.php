<?php

declare(strict_types=1);

namespace App\Livewire\System\Users;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Services\OfficeGuy\SystemBillingService;
use App\Services\SystemAuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Show extends Component
{
    public User $user;

    public ?string $pendingAction = null;

    public string $confirmPassword = '';

    public array $organizationSubscriptions = [];

    #[Layout('layouts.app')]
    #[Title('User')]
    public function mount(User $user, SystemBillingService $billingService): void
    {
        $this->user = $user;
        $this->loadSubscriptions($billingService);
    }

    protected function loadSubscriptions(SystemBillingService $billingService): void
    {
        foreach ($this->user->organizations as $org) {
            $subscription = $billingService->getOrganizationSubscription($org);
            $this->organizationSubscriptions[$org->id] = $subscription;
        }
    }

    public function syncOrganization(int $orgId, SystemBillingService $billingService): void
    {
        $org = Organization::find($orgId);
        if ($org) {
            $billingService->syncOrganizationSubscriptions($org);
            $this->loadSubscriptions($billingService);
            session()->flash('success', __('Subscriptions synced successfully for :name', ['name' => $org->name]));
        }
    }

    public function requestAction(string $action): void
    {
        $this->pendingAction = $action;
        $this->confirmPassword = '';
        $this->resetValidation();
    }

    public function cancelConfirm(): void
    {
        $this->pendingAction = null;
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
        $this->pendingAction = null;
        $this->confirmPassword = '';
        match ($action) {
            'promoteToSystemAdmin' => $this->promoteToSystemAdmin(),
            'demoteSystemAdmin' => $this->demoteSystemAdmin(),
            'disableUser' => $this->disableUser(),
            'forcePasswordReset' => $this->forcePasswordReset(),
            'invalidateSessions' => $this->invalidateSessions(),
            default => null,
        };
        $this->user->refresh();
    }

    public function render(): View
    {
        $this->user->load('organizations');
        $eventsCount = Event::whereIn('organization_id', $this->user->organizations()->pluck('organizations.id'))->count();

        return view('livewire.system.users.show', [
            'eventsCount' => $eventsCount,
        ]);
    }

    protected function promoteToSystemAdmin(): void
    {
        if ($this->user->is_system_admin) {
            return;
        }
        $this->user->update(['is_system_admin' => true]);
        SystemAuditLogger::log(auth()->user(), 'system_admin.promoted', $this->user, ['user_id' => $this->user->id, 'email' => $this->user->email]);
    }

    protected function demoteSystemAdmin(): void
    {
        if ($this->user->id === auth()->id()) {
            return;
        }
        if (! $this->user->is_system_admin) {
            return;
        }
        $this->user->update(['is_system_admin' => false]);
        SystemAuditLogger::log(auth()->user(), 'system_admin.demoted', $this->user, ['user_id' => $this->user->id, 'email' => $this->user->email]);
    }

    protected function disableUser(): void
    {
        $this->user->update(['is_disabled' => true]);
        $this->invalidateSessionsQuiet();
        SystemAuditLogger::log(auth()->user(), 'user.disabled', $this->user, []);
    }

    protected function forcePasswordReset(): void
    {
        $newPassword = Str::random(16);
        $this->user->update(['password' => Hash::make($newPassword)]);
        $this->invalidateSessionsQuiet();
        SystemAuditLogger::log(auth()->user(), 'user.force_password_reset', $this->user, []);
    }

    protected function invalidateSessions(): void
    {
        $this->invalidateSessionsQuiet();
        SystemAuditLogger::log(auth()->user(), 'user.sessions_invalidated', $this->user, []);
    }

    protected function invalidateSessionsQuiet(): void
    {
        DB::table('sessions')->where('user_id', $this->user->id)->delete();
    }
}
