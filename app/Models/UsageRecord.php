<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property int $product_id
 * @property string $metric_key
 * @property int $quantity
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereMetricKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereRecordedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperUsageRecord
 */
class UsageRecord extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'account_id',
        'product_id',
        'metric_key',
        'quantity',
        'recorded_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'recorded_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
