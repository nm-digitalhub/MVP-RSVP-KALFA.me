<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Usage tracking per account per feature_key per period. No enforcement in this phase.
 *
 * @property int $id
 * @property int $account_id
 * @property string $feature_key
 * @property int $period_key
 * @property int $usage_count
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereFeatureKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage wherePeriodKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereUsageCount($value)
 * @mixin \Eloquent
 * @mixin IdeHelperAccountFeatureUsage
 */
class AccountFeatureUsage extends Model
{
    protected $table = 'account_feature_usage';

    protected $fillable = [
        'account_id',
        'feature_key',
        'period_key',
        'usage_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
