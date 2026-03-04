<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Usage tracking per account per feature_key per period. No enforcement in this phase.
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
