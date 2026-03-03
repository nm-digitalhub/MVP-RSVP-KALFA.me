<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->organizations()->where('organizations.id', $event->organization_id)->exists();
    }

    public function create(User $user, int $organizationId): bool
    {
        return $user->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->organizations()->where('organizations.id', $event->organization_id)->exists();
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->organizations()->where('organizations.id', $event->organization_id)->exists();
    }

    public function initiatePayment(User $user, Event $event): bool
    {
        return (new OrganizationPolicy)->initiateBilling($user, $event->organization);
    }
}
