<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Account layer for entitlements and optional SUMIT customer mapping.
 * type: organization | individual. No enforcement or gating in this phase.
 */
class Account extends Model
{
    protected $fillable = [
        'type',
        'name',
        'owner_user_id',
        'sumit_customer_id',
    ];

    protected function casts(): array
    {
        return [
            'sumit_customer_id' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'account_id');
    }

    public function eventsBilling(): HasMany
    {
        return $this->hasMany(EventBilling::class, 'account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'account_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(AccountEntitlement::class, 'account_id');
    }

    public function featureUsage(): HasMany
    {
        return $this->hasMany(AccountFeatureUsage::class, 'account_id');
    }

    public function billingIntents(): HasMany
    {
        return $this->hasMany(BillingIntent::class, 'account_id');
    }
}
