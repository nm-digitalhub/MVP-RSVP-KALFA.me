<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationUserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OfficeGuy\LaravelSumitGateway\Contracts\HasSumitCustomer;
use OfficeGuy\LaravelSumitGateway\Support\Traits\HasSumitCustomerTrait;

class Organization extends Model implements HasSumitCustomer
{
    use HasFactory, HasSumitCustomerTrait;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'billing_email',
        'settings',
        'is_suspended',
    ];

    /**
     * Get the SUMIT customer ID from the linked account if not set on organization.
     */
    public function getSumitCustomerId(): ?int
    {
        return $this->sumit_customer_id ?? $this->account?->sumit_customer_id;
    }

    /**
     * Get the customer's email address for SUMIT documents.
     */
    public function getSumitCustomerEmail(): ?string
    {
        return $this->billing_email ?? $this->account?->owner?->email;
    }

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
