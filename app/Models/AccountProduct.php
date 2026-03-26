<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountProductStatus;
use App\Observers\AccountProductObserver;
use App\Services\FeatureResolver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property int $product_id
 * @property AccountProductStatus $status
 * @property \Illuminate\Support\Carbon|null $granted_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $granted_by
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\User|null $grantedBy
 * @property-read \App\Models\Product $product
 * @method static Builder<static>|AccountProduct active()
 * @method static Builder<static>|AccountProduct newModelQuery()
 * @method static Builder<static>|AccountProduct newQuery()
 * @method static Builder<static>|AccountProduct query()
 * @method static Builder<static>|AccountProduct whereAccountId($value)
 * @method static Builder<static>|AccountProduct whereCreatedAt($value)
 * @method static Builder<static>|AccountProduct whereExpiresAt($value)
 * @method static Builder<static>|AccountProduct whereGrantedAt($value)
 * @method static Builder<static>|AccountProduct whereGrantedBy($value)
 * @method static Builder<static>|AccountProduct whereId($value)
 * @method static Builder<static>|AccountProduct whereMetadata($value)
 * @method static Builder<static>|AccountProduct whereProductId($value)
 * @method static Builder<static>|AccountProduct whereStatus($value)
 * @method static Builder<static>|AccountProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperAccountProduct
 */
#[ObservedBy([AccountProductObserver::class])]
class AccountProduct extends Model
{
    protected $fillable = [
        'account_id',
        'product_id',
        'status',
        'granted_at',
        'expires_at',
        'granted_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountProductStatus::class,
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $flushResolvedFeatures = function (AccountProduct $accountProduct): void {
            $featureKeys = $accountProduct->product()
                ->with('productEntitlements')
                ->first()?->productEntitlements
                ->pluck('feature_key')
                ->all() ?? [];

            app(FeatureResolver::class)->forgetMany(
                $accountProduct->account()->firstOrFail(),
                $featureKeys,
            );
        };

        static::saved($flushResolvedFeatures);
        static::deleted($flushResolvedFeatures);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query
            ->where('status', AccountProductStatus::Active->value)
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
