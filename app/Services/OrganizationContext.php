<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Organization context for multi-tenant dashboard.
 * DB (User::current_organization_id) is the single source of truth.
 * This service does not hold independent state; session may mirror DB for compatibility.
 * Controllers must read organization via current() — never organization_id from request.
 */
class OrganizationContext
{
    public const SESSION_KEY = 'active_organization_id';

    /**
     * Set active organization: write to DB via User and mirror to session.
     */
    public function set(Organization $organization): void
    {
        $user = Auth::user();
        if (! $user || ! $this->validateMembership($user, $organization)) {
            $this->clear();

            return;
        }
        $user->update(['current_organization_id' => $organization->id]);
        Session::put(self::SESSION_KEY, $organization->id);
    }

    /**
     * Set active organization by ID: write to DB and mirror to session.
     */
    public function setById(int $organizationId): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $organization = Organization::find($organizationId);
        if (! $organization || ! $this->validateMembership($user, $organization)) {
            $this->clear();

            return false;
        }
        $user->update(['current_organization_id' => $organization->id]);
        Session::put(self::SESSION_KEY, $organization->id);

        return true;
    }

    /**
     * Current active organization — resolved strictly from DB (User::currentOrganization).
     * Session is never the primary source.
     */
    public function current(): ?Organization
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return $user->currentOrganization;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function validateMembership(?Authenticatable $user, Organization $organization): bool
    {
        if (! $user instanceof \App\Models\User) {
            return false;
        }

        return $user->organizations()->where('organizations.id', $organization->id)->exists();
    }
}
