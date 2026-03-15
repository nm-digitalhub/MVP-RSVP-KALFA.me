<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrganizationUserRole;
use App\Enums\PaymentStatus;
use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronises Spatie team-scoped permissions for every Owner/Admin
 * of every organization linked to an Account.
 *
 * Grant trigger  : AccountProduct becomes active AND account has a
 *                  succeeded payment OR the product was manually granted
 *                  by a system admin (granted_by IS NOT NULL).
 * Revoke trigger : Account has no more active products that satisfy the
 *                  above condition.
 */
final class PermissionSyncService
{
    /** Permissions granted to org Owner/Admin once billing is active. */
    private const TENANT_PERMISSIONS = [
        'view-event-details',
        'manage-event-guests',
        'manage-event-tables',
        'send-invitations',
    ];

    public function syncForAccount(Account $account): void
    {
        $shouldHaveAccess = $this->hasActivePaidOrGranted($account);

        $account->loadMissing('organizations');

        foreach ($account->organizations as $organization) {
            $this->syncForOrganization($organization, $shouldHaveAccess);
        }
    }

    /**
     * An account qualifies when it has at least one active AccountProduct AND:
     *  - a succeeded payment exists (subscription / event payment flow), OR
     *  - the product was manually granted by a system admin (granted_by set).
     */
    public function hasActivePaidOrGranted(Account $account): bool
    {
        $activeProducts = $account->activeAccountProducts();

        if (! $activeProducts->exists()) {
            return false;
        }

        $hasPaid = $account->payments()
            ->where('status', PaymentStatus::Succeeded->value)
            ->exists();

        if ($hasPaid) {
            return true;
        }

        // Manual admin grant: granted_by is set (system admin explicitly granted)
        return $activeProducts->whereNotNull('granted_by')->exists();
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function syncForOrganization(Organization $organization, bool $grant): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($organization->id);

        $permissions = Permission::whereIn('name', self::TENANT_PERMISSIONS)->get();

        $ownerRoles = [
            OrganizationUserRole::Owner->value,
            OrganizationUserRole::Admin->value,
        ];

        $organization->users()
            ->wherePivotIn('role', $ownerRoles)
            ->each(function (User $user) use ($permissions, $grant): void {
                // Clear cached permission state for this user
                $user->unsetRelation('permissions')->unsetRelation('roles');

                if ($grant) {
                    $user->givePermissionTo($permissions);
                } else {
                    $user->revokePermissionTo($permissions);
                }
            });
    }
}
