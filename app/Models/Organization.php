<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationUserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'billing_email',
        'settings',
        'is_suspended',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_suspended' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->using(OrganizationUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'organization_id');
    }

    public function eventsBilling(): HasMany
    {
        return $this->hasMany(EventBilling::class, 'organization_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'organization_id');
    }

    /**
     * Owner of the organization (first user with Owner role). For system admin display.
     */
    public function invitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function owner(): ?User
    {
        return $this->users()->wherePivot('role', OrganizationUserRole::Owner->value)->first();
    }
}
