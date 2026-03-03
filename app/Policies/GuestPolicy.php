<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function view(User $user, Guest $guest): bool
    {
        return $user->organizations()->where('organizations.id', $guest->event->organization_id)->exists();
    }

    public function create(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function update(User $user, Guest $guest): bool
    {
        return $user->organizations()->where('organizations.id', $guest->event->organization_id)->exists();
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $user->organizations()->where('organizations.id', $guest->event->organization_id)->exists();
    }
}
