<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountProductStatus;
use App\Enums\AccountSubscriptionStatus;
use App\Enums\EntitlementType;
use App\Events\ProductEngineEvent;
use App\Services\FeatureResolver;
use App\Services\SubscriptionManager;
use App\Services\SubscriptionService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OfficeGuy\LaravelSumitGateway\Contracts\HasSumitCustomer;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;
use OfficeGuy\LaravelSumitGateway\Support\Traits\HasSumitCustomerTrait;

/**
 * Account layer for entitlements and optional SUMIT customer mapping.
 *
 * type: organization | individual. No enforcement or gating in this phase.
 *
 * @property int $id
 * @property string $type
 * @property int|null $owner_user_id
 * @property int|null $sumit_customer_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $name
 * @property string|null $twilio_subaccount_sid
 * @property int $credit_balance_agorot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountProduct> $accountProducts
 * @property-read int|null $account_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountProduct> $activeAccountProducts
 * @property-read int|null $active_account_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountSubscription> $activeSubscriptions
 * @property-read int|null $active_subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BillingIntent> $billingIntents
 * @property-read int|null $billing_intents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountEntitlement> $entitlements
 * @property-read int|null $entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventBilling> $eventsBilling
 * @property-read int|null $events_billing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountFeatureUsage> $featureUsage
 * @property-read int|null $feature_usage_count
 * @property-read string|null $company
 * @property-read string|null $email
 * @property-read string|null $phone
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization> $organizations
 * @property-read int|null $organizations_count
 * @property-read \App\Models\User|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OfficeGuyToken> $paymentMethods
 * @property-read int|null $payment_methods_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Database\Factories\AccountFactory factory($count = null, $state = [])
 * @method static Builder<static>|Account newModelQuery()
 * @method static Builder<static>|Account newQuery()
 * @method static Builder<static>|Account query()
 * @method static Builder<static>|Account whereCreatedAt($value)
 * @method static Builder<static>|Account whereCreditBalanceAgorot($value)
 * @method static Builder<static>|Account whereId($value)
 * @method static Builder<static>|Account whereName($value)
 * @method static Builder<static>|Account whereOwnerUserId($value)
 * @method static Builder<static>|Account whereSumitCustomerId($value)
 * @method static Builder<static>|Account whereTwilioSubaccountSid($value)
 * @method static Builder<static>|Account whereType($value)
 * @method static Builder<static>|Account whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperAccount
 */
class Account extends Model implements HasSumitCustomer
{
    use HasFactory, HasSumitCustomerTrait;

    protected $fillable = [
        'type',
        'name',
        'owner_user_id',
        'sumit_customer_id',
        'credit_balance_agorot',
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
            ->where('status', AccountSubscriptionStatus::Active->value)
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Check if account has billing access (active product, subscription, or trial).
     * Cached for 60 seconds for performance.
     */
    public function hasBillingAccess(): bool
    {
        return Cache::remember("account:{$this->id}:billing_access", 60, function (): bool {
            return $this->activeAccountProducts()->exists()
                || $this->activeSubscriptions()->exists()
                || $this->subscriptions()
                    ->where('status', AccountSubscriptionStatus::Trial->value)
                    ->where('trial_ends_at', '>', now())
                    ->exists();
        });
    }

    /**
     * Invalidate the billing access cache.
     * Call this after granting products, activating subscriptions, or modifying trials.
     */
    public function invalidateBillingAccessCache(): void
    {
        Cache::forget("account:{$this->id}:billing_access");
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

            $this->invalidateBillingAccessCache();

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
        $subscription = app(SubscriptionService::class)->startTrial(
            $this,
            $plan,
            $trialEndsAt,
            $startedAt,
            $metadata,
        );

        $this->invalidateBillingAccessCache();

        return $subscription;
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
