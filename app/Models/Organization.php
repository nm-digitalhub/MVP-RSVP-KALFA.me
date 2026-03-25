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

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $billing_email
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_suspended
 * @property int|null $account_id
 * @property-read \App\Models\Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventBilling> $eventsBilling
 * @property-read int|null $events_billing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrganizationInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\OrganizationUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\OrganizationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereBillingEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereIsSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperOrganization
 */
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
