<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountProductStatus;
use App\Enums\EntitlementType;
use App\Events\ProductEngineEvent;
use App\Services\FeatureResolver;
use App\Services\SubscriptionManager;
use App\Services\SubscriptionService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use OfficeGuy\LaravelSumitGateway\Contracts\HasSumitCustomer;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;
use OfficeGuy\LaravelSumitGateway\Support\Traits\HasSumitCustomerTrait;

/**
 * Account layer for entitlements and optional SUMIT customer mapping.
 * type: organization | individual. No enforcement or gating in this phase.
 */
class Account extends Model implements HasSumitCustomer
{
    use HasSumitCustomerTrait;

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

    public function getSumitCustomerEmail(): ?string
    {
        return $this->owner?->email;
    }

    public function getSumitCustomerName(): ?string
    {
        return $this->name ?: $this->owner?->name;
    }

    public function getSumitCustomerPhone(): ?string
    {
        return $this->owner?->phone ?? null;
    }

    public function getSumitCustomerBusinessId(): ?string
    {
        return null;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->getSumitCustomerEmail();
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->getSumitCustomerPhone();
    }

    public function getCompanyAttribute(): ?string
    {
        return $this->name;
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

    public function accountProducts(): HasMany
    {
        return $this->hasMany(AccountProduct::class, 'account_id');
    }

    public function activeAccountProducts(): HasMany
    {
        return $this->accountProducts()
            ->where('status', AccountProductStatus::Active->value)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function featureUsage(): HasMany
    {
        return $this->hasMany(AccountFeatureUsage::class, 'account_id');
    }

    public function billingIntents(): HasMany
    {
        return $this->hasMany(BillingIntent::class, 'account_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AccountSubscription::class, 'account_id');
    }

    public function paymentMethods(): MorphMany
    {
        return $this->morphMany(OfficeGuyToken::class, 'owner');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()
            ->where('status', \App\Enums\AccountSubscriptionStatus::Active->value)
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Grant all entitlements from a product to this account.
     */
    public function grantProduct(
        Product $product,
        ?int $grantedBy = null,
        ?CarbonInterface $expiresAt = null,
        array $metadata = [],
    ): AccountProduct {
        return DB::transaction(function () use ($product, $grantedBy, $expiresAt, $metadata): AccountProduct {
            $assignment = $this->activeAccountProducts()
                ->where('product_id', $product->id)
                ->latest('id')
                ->first();

            if ($assignment === null) {
                $assignment = $this->accountProducts()->create([
                    'product_id' => $product->id,
                    'status' => AccountProductStatus::Active,
                    'granted_at' => now(),
                    'expires_at' => $expiresAt,
                    'granted_by' => $grantedBy,
                    'metadata' => $metadata !== [] ? $metadata : null,
                ]);
            } else {
                $assignment->update([
                    'expires_at' => $expiresAt ?? $assignment->expires_at,
                    'granted_by' => $grantedBy ?? $assignment->granted_by,
                    'metadata' => $metadata !== [] ? $metadata : $assignment->metadata,
                ]);
            }

            $product->loadMissing('productEntitlements');
            $resolvedFeatureKeys = [];

            foreach ($product->productEntitlements as $productEntitlement) {
                $this->entitlements()->updateOrCreate(
                    ['product_entitlement_id' => $productEntitlement->id],
                    [
                        'feature_key' => $productEntitlement->feature_key,
                        'value' => $productEntitlement->value,
                        'type' => $productEntitlement->type,
                        'expires_at' => $expiresAt,
                    ]
                );

                $resolvedFeatureKeys[] = $productEntitlement->feature_key;
            }

            app(FeatureResolver::class)->forgetMany($this, $resolvedFeatureKeys);

            ProductEngineEvent::dispatch(
                'product.granted',
                $this,
                $product,
                payload: [
                    'product_id' => $product->id,
                    'account_product_id' => $assignment->id,
                ],
            );

            return $assignment->fresh(['product']);
        });
    }

    public function overrideFeature(
        string $featureKey,
        mixed $value,
        ?EntitlementType $type = null,
        ?CarbonInterface $expiresAt = null,
    ): AccountEntitlement {
        $resolvedType = $type ?? $this->inferEntitlementType($featureKey, $value);

        $override = $this->entitlements()->updateOrCreate(
            [
                'feature_key' => $featureKey,
                'product_entitlement_id' => null,
            ],
            [
                'value' => $this->normalizeEntitlementValue($value, $resolvedType),
                'type' => $resolvedType,
                'expires_at' => $expiresAt,
            ]
        );

        app(FeatureResolver::class)->forget($this, $featureKey);

        return $override;
    }

    public function subscribeToPlan(
        ProductPlan $plan,
        ?CarbonInterface $startedAt = null,
        array $metadata = [],
    ): AccountSubscription {
        return app(SubscriptionManager::class)->subscribe(
            $this,
            $plan,
            $startedAt,
            $metadata,
        );
    }

    public function startTrial(
        ProductPlan $plan,
        ?CarbonInterface $trialEndsAt = null,
        ?CarbonInterface $startedAt = null,
        array $metadata = [],
    ): AccountSubscription {
        return app(SubscriptionService::class)->startTrial(
            $this,
            $plan,
            $trialEndsAt,
            $startedAt,
            $metadata,
        );
    }

    private function inferEntitlementType(string $featureKey, mixed $value): EntitlementType
    {
        if (is_bool($value)) {
            return EntitlementType::Boolean;
        }

        if (is_int($value) || is_float($value)) {
            return EntitlementType::Number;
        }

        $existingEntitlement = $this->entitlements()
            ->where('feature_key', $featureKey)
            ->latest('id')
            ->first();

        if ($existingEntitlement?->type instanceof EntitlementType) {
            return $existingEntitlement->type;
        }

        return EntitlementType::Text;
    }

    private function normalizeEntitlementValue(mixed $value, EntitlementType $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            EntitlementType::Boolean => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}
