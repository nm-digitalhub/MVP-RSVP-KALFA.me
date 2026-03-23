# Products Engine Architecture

**Last Updated:** 2026-03-23
**Component:** Products & Subscriptions System

---

## Overview

The Products Engine is a feature entitlement and subscription management system that allows:
- Creating products with multiple plans and pricing tiers
- Defining feature entitlements and usage limits
- Tracking account-level feature usage
- Managing subscriptions and billing

---

## Database Schema

### Core Tables

| Table | Purpose |
|-------|---------|
| `products` | Product catalog |
| `product_plans` | Plans within products |
| `product_prices` | Pricing per plan |
| `product_entitlements` | Feature grants |
| `product_features` | Feature definitions |
| `product_limits` | Usage limits |
| `account_products` | Account subscriptions |
| `account_entitlements` | Granted features |
| `account_feature_usage` | Usage tracking |
| `usage_records` | Usage history |

---

## Models

### Product Hierarchy

```
Product
  ├── ProductPlan
  │   └── ProductPrice
  ├── ProductEntitlement (features granted)
  ├── ProductFeature (feature definitions)
  └── ProductLimit (usage limits)
```

### Account Hierarchy

```
Account
  ├── AccountProduct (subscription to a plan)
  ├── AccountEntitlement (granted features)
  └── AccountFeatureUsage (usage tracking)
```

---

## Services

### Core Services

| Service | Responsibility |
|----------|------------------|
| `FeatureResolver` | Resolve feature availability for accounts |
| `UsageMeter` | Track and record feature usage |
| `UsagePolicyService` | Enforce usage policies and limits |
| `ProductIntegrityChecker` | Validate product configurations |
| `SubscriptionManager` | High-level subscription operations |
| `SubscriptionService` | Subscription lifecycle |
| `SubscriptionSyncService` | Sync with OfficeGuy |

---

## Key Workflows

### 1. Feature Resolution

```php
// app/Services/FeatureResolver.php
class FeatureResolver
{
    public function hasFeature(Account $account, string $featureKey): bool
    {
        // Check account entitlements
        return $account->entitlements()
            ->where('feature_key', $featureKey)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function getLimit(Account $account, string $limitKey): ?int
    {
        // Get limit value from product limits
        $entitlement = $account->entitlements()
            ->where('feature_key', $limitKey)
            ->first();

        return $entitlement?->value ?? null;
    }
}
```

### 2. Usage Metering

```php
// app/Services/UsageMeter.php
class UsageMeter
{
    public function recordUsage(Account $account, string $metricKey, int $quantity): void
    {
        UsageRecord::create([
            'account_id' => $account->id,
            'product_id' => $account->activeProduct()->id,
            'metric_key' => $metricKey,
            'quantity' => $quantity,
            'recorded_at' => now(),
        ]);
    }

    public function getCurrentUsage(Account $account, string $periodKey): int
    {
        return $account->featureUsage()
            ->where('period_key', $periodKey)
            ->sum('usage_count');
    }
}
```

### 3. Policy Enforcement

```php
// app/Services/UsagePolicyService.php
class UsagePolicyService
{
    public function checkLimit(Account $account, string $limitKey, int $requested): UsagePolicyDecision
    {
        $current = $this->usageMeter->getCurrentUsage($account, $periodKey());
        $max = $this->featureResolver->getLimit($account, $limitKey);

        if ($current + $requested > $max) {
            return UsagePolicyDecision::Denied;
        }

        return UsagePolicyDecision::Allowed;
    }
}
```

---

## Product Integrity Checking

The `ProductIntegrityChecker` validates product configurations:

```php
// app/Services/ProductIntegrityChecker.php
class ProductIntegrityChecker
{
    public function validate(Product $product): array
    {
        $errors = [];

        // Check each plan has at least one price
        foreach ($product->plans as $plan) {
            if ($plan->prices->isEmpty()) {
                $errors[] = "Plan {$plan->name} has no prices";
            }
        }

        // Check features have valid entitlements
        foreach ($product->features as $feature) {
            if ($feature->entitlements->isEmpty()) {
                $errors[] = "Feature {$feature->feature_key} has no entitlements defined";
            }
        }

        return $errors;
    }
}
```

---

## API Endpoints

### Product Management

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/system/products` | GET | List products |
| `/api/system/products/{id}` | GET | Get product details |
| `/api/system/products` | POST | Create product |
| `/api/system/products/{id}` | PUT | Update product |

### Plan Management

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/system/products/{product}/plans` | GET | List plans |
| `/api/system/products/{product}/plans` | POST | Create plan |
| `/api/system/products/{product}/plans/{id}` | PUT | Update plan |

### Entitlement Management

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/system/products/{product}/entitlements` | GET | List entitlements |
| `/api/system/products/{product}/entitlements` | POST | Create entitlement |
| `/api/accounts/{account}/entitlements` | GET | Get account entitlements |
| `/api/accounts/{account}/usage` | GET | Get usage metrics |

---

## Livewire Components

### System Products

| Component | Location | Purpose |
|-----------|----------|---------|
| `Products/Index` | `app/Livewire/System/Products/Index.php` | Product list |
| `Products/Show` | `app/Livewire/System/Products/Show.php` | Product details |
| `Products/CreateProductWizard` | `app/Livewire/System/Products/CreateProductWizard.php` | Create product |
| `Products/ProductTree` | `app/Livewire/System/Products/ProductTree.php` | Product tree view |

### Entitlement Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `Products/EntitlementRow` | `app/Livewire/System/Products/EntitlementRow.php` | Entitlement row |
| `Products/ProductCard` | `app/Livewire/System/Products/ProductCard.php` | Product card |
| `Products/ProductStatusBadge` | `app/Livewire/System/Products/ProductStatusBadge.php` | Status indicator |

---

## Enums

### Product Status

```php
// app/Enums/ProductStatus.php
enum ProductStatus: string
{
    case DRAFT;
    case ACTIVE;
    case ARCHIVED;
}
```

### Account Product Status

```php
// app/Enums/AccountProductStatus.php
enum AccountProductStatus: string
{
    case PENDING;
    case ACTIVE;
    case SUSPENDED;
    case CANCELLED;
    case EXPIRED;
}
```

### Entitlement Type

```php
// app/Enums/EntitlementType.php
enum EntitlementType: string
{
    case BOOLEAN;
    case COUNT;
    case USAGE;
}
```

---

## Usage Example

### Creating a Product with Features

```php
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPrice;
use App\Models\ProductEntitlement;

// Create product
$product = Product::create([
    'name' => 'Event Platform',
    'slug' => 'event-platform',
    'status' => ProductStatus::ACTIVE,
]);

// Create plan
$plan = ProductPlan::create([
    'product_id' => $product->id,
    'name' => 'Professional',
    'slug' => 'professional',
    'is_active' => true,
]);

// Add price
ProductPrice::create([
    'product_plan_id' => $plan->id,
    'currency' => 'ILS',
    'amount' => 9900, // 99.00 ILS
    'billing_cycle' => 'monthly',
    'is_active' => true,
]);

// Add feature entitlement
ProductEntitlement::create([
    'product_id' => $product->id,
    'feature_key' => 'max_events',
    'value' => '100',
    'type' => EntitlementType::COUNT,
    'label' => 'Max Events',
    'is_active' => true,
]);
```

### Checking Feature Access

```php
use App\Services\FeatureResolver;
use App\Services\UsagePolicyService;

class EventService
{
    public function __construct(
        private FeatureResolver $featureResolver,
        private UsagePolicyService $policyService,
    ) {}

    public function createEvent(Account $account): void
    {
        // Check if account can create more events
        $maxEvents = $this->featureResolver->getLimit($account, 'max_events');
        $currentUsage = $this->usageMeter->getCurrentUsage($account, 'monthly');

        if ($currentUsage >= $maxEvents) {
            throw new \Exception('Event limit reached');
        }

        // Create event...
    }
}
```

---

## Monitoring & Observability

### Product Operations Monitor

```php
// app/Services/ProductEngineOperationsMonitor.php
class ProductEngineOperationsMonitor
{
    public function checkHealth(): array
    {
        return [
            'products_count' => Product::count(),
            'active_plans' => ProductPlan::where('is_active', true)->count(),
            'entitlements' => ProductEntitlement::where('is_active', true)->count(),
            'accounts_with_products' => AccountProduct::where('status', AccountProductStatus::ACTIVE)->count(),
        ];
    }
}
```

### Artisan Commands

| Command | Purpose |
|---------|---------|
| `php artisan product:check-integrity` | Validate product configurations |
| `php artisan product:process-trial-expirations` | Process expiring trials |
| `php artisan product:health` | Check products engine health |

---

## Best Practices

1. **Always use FeatureResolver** - Never hardcode feature checks
2. **Validate limits before operations** - Use UsagePolicyService
3. **Record all usage** - Use UsageMeter for tracking
4. **Check product integrity** - Run integrity checks before deployment
5. **Use enums for status** - ProductStatus, AccountProductStatus, etc.

---

*See also:*
- [[Architecture/Overview]]
- [[Architecture/Billing]]
- [[Architecture/Subscriptions]]
