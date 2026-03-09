<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $this->belongsToOrganization($user, $organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->isOwnerOrAdmin($user, $organization);
    }

    public function initiateBilling(User $user, Organization $organization): bool
    {
        return $this->isOwnerOrAdmin($user, $organization);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->isOwnerOrAdmin($user, $organization);
    }

    private function belongsToOrganization(User $user, Organization $organization): bool
    {
        return $user->organizations()->where('organizations.id', $organization->id)->exists();
    }

    private function isOwnerOrAdmin(User $user, Organization $organization): bool
    {
        $pivot = $user->organizations()->where('organizations.id', $organization->id)->first()?->pivot;
        if (! $pivot) {
            return false;
        }
        $role = $pivot->role instanceof OrganizationUserRole ? $pivot->role : OrganizationUserRole::tryFrom($pivot->role);

        return $role === OrganizationUserRole::Owner || $role === OrganizationUserRole::Admin;
    }
}
