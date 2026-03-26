<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Purchase abstraction: links account to a future payment/checkout. No enforcement in this phase.
 *
 * @property int $id
 * @property int $account_id
 * @property string $status
 * @property string|null $intent_type
 * @property string|null $payable_type
 * @property int|null $payable_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read Model|\Eloquent|null $payable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereIntentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent wherePayableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent wherePayableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperBillingIntent
 */
class BillingIntent extends Model
{
    protected $fillable = [
        'account_id',
        'status',
        'intent_type',
        'payable_type',
        'payable_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
