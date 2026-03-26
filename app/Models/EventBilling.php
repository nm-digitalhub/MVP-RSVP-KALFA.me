<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventBillingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $event_id
 * @property int|null $plan_id
 * @property int $amount_cents
 * @property string $currency
 * @property EventBillingStatus $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $account_id
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\Plan|null $plan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereAmountCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperEventBilling
 */
class EventBilling extends Model
{
    protected $table = 'events_billing';

    protected $fillable = [
        'account_id',
        'organization_id',
        'event_id',
        'plan_id',
        'amount_cents',
        'currency',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => EventBillingStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
