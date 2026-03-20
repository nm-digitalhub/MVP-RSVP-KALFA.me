<?php

namespace App\Models;

use App\Enums\OrganizationUserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, WebAuthnAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, WebAuthnAuthentication;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_organization_id',
        'is_system_admin',
        'last_login_at',
        'is_disabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'enabled_mfa' => 'boolean',
            'mfa_sent_at' => 'datetime',
            'is_system_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'is_disabled' => 'boolean',
        ];
    }

    /**
     * Organizations where this user is owner (for system admin display).
     */
    public function ownedOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->using(OrganizationUser::class)
            ->withPivot('role')
            ->withTimestamps()
            ->wherePivot('role', OrganizationUserRole::Owner->value);
    }

    /**
     * Organizations the user belongs to (Event SaaS multi-tenant).
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->using(OrganizationUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Active organization for dashboard context (multi-tenant).
     * When system admin is impersonating (session key set), skip membership check.
     */
    public ?Organization $currentOrganization {
        get {
            if ($this->current_organization_id === null) {
                return null;
            }
            $org = Organization::find($this->current_organization_id);
            if ($org === null) {
                return null;
            }
            if ($this->is_system_admin && session()->has('impersonation.original_organization_id')) {
                return $org;
            }
            if (! $this->organizations()->where('organizations.id', $org->id)->exists()) {
                return null;
            }

            return $org;
        }
    }
}
