<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrganizationUserRole;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OrganizationMemberService
{
    /**
     * Invite a new member via email.
     */
    public function invite(Organization $organization, string $email, OrganizationUserRole $role): OrganizationInvitation
    {
        // Delete any existing pending invitation for this email in this organization
        OrganizationInvitation::where('organization_id', $organization->id)
            ->where('email', $email)
            ->delete();

        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new OrganizationInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Direct add a user to an organization (for system admin).
     */
    public function addMember(Organization $organization, User $user, OrganizationUserRole $role): void
    {
        DB::transaction(function () use ($organization, $user, $role) {
            $organization->users()->syncWithoutDetaching([
                $user->id => ['role' => $role->value],
            ]);

            $this->syncSpatieRole($organization, $user, $role);
        });
    }

    /**
     * Accept an invitation.
     */
    public function acceptInvitation(string $token, User $user): Organization
    {
        $invitation = OrganizationInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            throw new \Exception(__('This invitation has expired.'));
        }

        DB::transaction(function () use ($invitation, $user) {
            $this->addMember($invitation->organization, $user, $invitation->role);

            // Set as current organization if not set
            if (! $user->current_organization_id) {
                $user->update(['current_organization_id' => $invitation->organization_id]);
            }

            $invitation->delete();
        });

        return $invitation->organization;
    }

    /**
     * Remove a member from the organization.
     */
    public function removeMember(Organization $organization, User $user): void
    {
        // Prevent removing the owner if they are the only owner
        $pivot = $organization->users()->where('user_id', $user->id)->first()?->pivot;
        if ($pivot && $pivot->role === OrganizationUserRole::Owner) {
            $otherOwnersCount = $organization->users()
                ->wherePivot('role', OrganizationUserRole::Owner->value)
                ->where('users.id', '!=', $user->id)
                ->count();

            if ($otherOwnersCount === 0) {
                throw new \Exception(__('Cannot remove the only owner of the organization.'));
            }
        }

        DB::transaction(function () use ($organization, $user) {
            $organization->users()->detach($user->id);

            // Clear Spatie roles for this team
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
            $user->syncRoles([]);

            // If it was the user's current organization, unset it
            if ($user->current_organization_id === $organization->id) {
                $user->update(['current_organization_id' => $user->organizations()->first()?->id]);
            }
        });
    }

    /**
     * Update a member's role.
     */
    public function updateRole(Organization $organization, User $user, OrganizationUserRole $role): void
    {
        DB::transaction(function () use ($organization, $user, $role) {
            $organization->users()->updateExistingPivot($user->id, [
                'role' => $role->value,
            ]);

            $this->syncSpatieRole($organization, $user, $role);
        });
    }

    /**
     * Sync Spatie roles based on our Pivot role.
     */
    protected function syncSpatieRole(Organization $organization, User $user, OrganizationUserRole $role): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $spatieRoleName = match ($role) {
            OrganizationUserRole::Owner, OrganizationUserRole::Admin => 'Organization Admin',
            OrganizationUserRole::Member => 'Organization Editor',
        };

        // Ensure role exists for this team
        Role::findOrCreate($spatieRoleName, 'web');

        $user->syncRoles([$spatieRoleName]);
    }
}
