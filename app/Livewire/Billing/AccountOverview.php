<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Models\Account;
use App\Services\OrganizationContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Tenant: Account overview for current organization.
 * If org has no account, show "Create account" (explicit action only).
 */
final class AccountOverview extends Component
{
    public function mount(OrganizationContext $context): mixed
    {
        $organization = $context->current();
        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        return null;
    }

    public function createAccount(OrganizationContext $context): mixed
    {
        $organization = $context->current();
        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        $this->authorize('update', $organization);

        if ($organization->account_id !== null) {
            return null;
        }

        $owner = $organization->owner();
        $account = Account::create([
            'type' => 'organization',
            'name' => $organization->name,
            'owner_user_id' => $owner?->id,
        ]);

        $organization->update(['account_id' => $account->id]);

        $this->grantOwnerPermissions($organization);

        return $this->redirect(route('billing.account'), navigate: true);
    }

    public function render(OrganizationContext $context): View
    {
        $organization = $context->current();
        $account = $organization?->account;

        return view('livewire.billing.account-overview', [
            'organization' => $organization,
            'account' => $account,
        ]);
    }

    /**
     * Grant all tenant-level permissions to every Owner/Admin of the org,
     * scoped to the organization's team ID.
     */
    private function grantOwnerPermissions(\App\Models\Organization $organization): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $permissions = Permission::whereIn('name', [
            'view-event-details',
            'manage-event-guests',
            'manage-event-tables',
            'send-invitations',
        ])->get();

        $ownerRoles = [
            \App\Enums\OrganizationUserRole::Owner->value,
            \App\Enums\OrganizationUserRole::Admin->value,
        ];

        $organization->users()
            ->wherePivotIn('role', $ownerRoles)
            ->each(function (\App\Models\User $user) use ($permissions): void {
                $user->givePermissionTo($permissions);
            });
    }
}
