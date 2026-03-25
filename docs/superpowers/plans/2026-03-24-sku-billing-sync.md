# SKU-Based Billing Sync Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable reliable matching between SUMIT subscriptions and local `ProductPlan` records using SKU as the stable integration contract.

**Architecture:**
1. Pass ProductPlan.sku through SumitBillingProvider metadata → OfficeGuySubscription.metadata
2. Subscription model uses sku from metadata when creating line items → SUMIT Items
3. SubscriptionSyncService matches by SKU when syncing from SUMIT to local ProductPlan

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL, SUMIT API

---

## Verified Facts

Based on codebase analysis:
- `SubscriptionSyncService::findProductPlanForSubscription()` accepts `OfficeGuySubscription` object (not array)
- Main sync method is `syncAccountSubscriptions(Account $account)` (not `syncForAccount`)
- ProductPlan already has `sku` field with unique constraint
- Current matching uses: name LIKE → metadata.product_plan_id (no SKU matching)

---

## File Structure

```
app/
├── Services/Billing/
│   └── SumitBillingProvider.php     [MODIFY] Add product_plan_sku to metadata
├── vendor/officeguy/laravel-sumit-gateway/
│   └── Models/Subscription.php          [MODIFY] getLineItems() to use sku from metadata
└── app/Services/
    └── SubscriptionSyncService.php  [MODIFY] findProductPlanForSubscription() to match by SKU
```

---

## Task 1: Add product_plan_sku to SumitBillingProvider Metadata

**Files:**
- Modify: `app/Services/Billing/SumitBillingProvider.php:106-111`

**Current Code (lines 98-112):**
```php
$officeGuySubscription = OfficeGuySubscriptionService::create(
    $account,
    $price->productPlan->name,
    $price->amount / 100,
    $price->currency,
    $billingCycle === ProductPriceBillingCycle::Yearly ? 12 : 1,
    null,
    $defaultToken->id,
    [
        'account_id' => $account->id,
        'product_id' => $price->productPlan->product_id,
        'product_plan_id' => $price->product_plan_id,
        'product_price_id' => $price->id,
    ],
);
```

**New Code:**
```php
$officeGuySubscription = OfficeGuySubscriptionService::create(
    $account,
    $price->productPlan->name,
    $price->amount / 100,
    $price->currency,
    $billingCycle === ProductPriceBillingCycle::Yearly ? 12 : 1,
    null,
    $defaultToken->id,
    [
        'account_id' => $account->id,
        'product_id' => $price->productPlan->product_id,
        'product_plan_id' => $price->product_plan_id,
        'product_price_id' => $price->id,
        'product_plan_sku' => $price->productPlan->sku, // ◄── SKU for SUMIT matching
    ],
);
```

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/Billing/SumitBillingProviderTest.php

use App\Models\Account;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPrice;
use App\Services\Billing\SumitBillingProvider;
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;
use OfficeGuy\LaravelSumitGateway\Services\SubscriptionService as OfficeGuySubscriptionService;

public function test_create_subscription_includes_product_plan_sku_in_metadata(): void
{
    $account = Account::factory()->create(['sumit_customer_id' => 12345]);
    $product = Product::factory()->create(['name' => 'Test Product']);
    $productPlan = ProductPlan::factory()->create([
        'product_id' => $product->id,
        'name' => 'Pro Plan',
        'sku' => 'PRO-MONTHLY-001',
    ]);
    $productPrice = ProductPrice::factory()->create([
        'product_plan_id' => $productPlan->id,
        'amount' => 9900,
        'currency' => 'ILS',
    ]);

    // Mock OfficeGuySubscriptionService
    OfficeGuySubscriptionService::shouldReceive('create')
        ->once()
        ->andReturn(new OfficeGuySubscription([
            'id' => 999,
            'metadata' => [],
        ]));

    OfficeGuySubscriptionService::shouldReceive('processCharge')
        ->andReturn(['success' => true, 'response' => ['Data' => ['RecurringID' => 'REC-123']]]);

    $provider = app(SumitBillingProvider::class);
    $result = $provider->createSubscription($account, $productPrice);

    // Verify metadata contains product_plan_sku - CORRECTED ASSERTION ORDER
    $this->assertArrayHasKey('product_plan_sku', $result['metadata']);
    $this->assertEquals('PRO-MONTHLY-001', $result['metadata']['product_plan_sku']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Billing/SumitBillingProviderTest.php::test_create_subscription_includes_product_plan_sku_in_metadata -v`
Expected: FAIL - "product_plan_sku not found in metadata"

- [ ] **Step 3: Write minimal implementation**

Edit `app/Services/Billing/SumitBillingProvider.php` line 106-111

Add `'product_plan_sku' => $price->productPlan->sku` to the metadata array.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Billing/SumitBillingProviderTest.php::test_create_subscription_includes_product_plan_sku_in_metadata -v`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Billing/SumitBillingProvider.php
git commit -m "feat(billing): add product_plan_sku to subscription metadata"
```

---

### Task 2: Update Subscription Model to Use SKU from Metadata

**Files:**
- Create: `tests/Unit/OfficeGuy/SubscriptionTest.php`
- Modify: `vendor/officeguy/laravel-sumit-gateway/src/Models/Subscription.php` (getLineItems method)

**Current Code:**
```php
public function getLineItems(): array
{
    return [
        [
            'name' => $this->name,
            'sku' => 'subscription_' . $this->id,  // ◄── PROBLEM: Uses internal ID
            'quantity' => 1,
            'unit_price' => (float) $this->amount,
            'product_id' => $this->id,
            'variation_id' => null,
        ],
    ];
}
```

**New Code:**
```php
public function getLineItems(): array
{
    // Use product_plan_sku from metadata if available
    $sku = $this->metadata['product_plan_sku']
        ?? $this->metadata['sumit_sku']  // Fallback for SUMIT-synced subscriptions
        ?? 'subscription_' . $this->id;  // Final fallback

    return [
        [
            'name' => $this->name,
            'sku' => $sku,
            'quantity' => 1,
            'unit_price' => (float) $this->amount,
            'product_id' => $this->id,
            'variation_id' => null,
        ],
    ];
}
```

- [ ] **Step 1: Write failing test**

```php
// tests/Unit/OfficeGuy/SubscriptionTest.php

use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;

public function test_get_line_items_uses_product_plan_sku_from_metadata(): void
{
    $subscription = new OfficeGuySubscription([
        'name' => 'Pro Plan',
        'amount' => 99.00,
        'metadata' => ['product_plan_sku' => 'PRO-MONTHLY-001'],
    ]);

    $items = $subscription->getLineItems();

    // CORRECTED: assertCount requires expected count first
    $this->assertCount(1, $items);
    $this->assertEquals('PRO-MONTHLY-001', $items[0]['sku']);
    $this->assertEquals('Pro Plan', $items[0]['name']);
}

public function test_get_line_items_falls_back_to_subscription_id(): void
{
    $subscription = new OfficeGuySubscription([
        'name' => 'Basic Plan',
        'amount' => 49.00,
        'metadata' => [], // No SKU in metadata
    ]);

    $items = $subscription->getLineItems();

    // CORRECTED: assertStringContainsString requires needle first, then haystack
    $this->assertStringContainsString('subscription_', $items[0]['sku']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/OfficeGuy/SubscriptionTest.php -v`
Expected: FAIL - "product_plan_sku not found"

- [ ] **Step 3: Write minimal implementation**

Edit `vendor/officeguy/laravel-sumit-gateway/src/Models/Subscription.php` getLineItems() method

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/OfficeGuy/SubscriptionTest.php -v`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add vendor/officeguy/laravel-sumit-gateway/src/Models/Subscription.php tests/Unit/OfficeGuy/SubscriptionTest.php
git commit -m "feat(subscription): use product_plan_sku from metadata in getLineItems"
```

---

### Task 3: Update SubscriptionSyncService to Match by SKU

**Files:**
- Create: `tests/Unit/Services/SubscriptionSyncServiceTest.php`
- Modify: `app/Services/SubscriptionSyncService.php:163-181`

**Current Code (verified lines 163-181):**
```php
private function findProductPlanForSubscription(OfficeGuySubscription $sumitSub): ?ProductPlan
{
    // Try to find by name match
    $productPlan = ProductPlan::where('name', 'like', '%'.$sumitSub->name.'%')
        ->where('is_active', true)
        ->first();

    if ($productPlan !== null) {
        return $productPlan;
    }

    // Try to find by metadata reference
    $sumitSubId = data_get($sumitSub, 'metadata.product_plan_id');
    if ($sumitSubId) {
        return ProductPlan::find($sumitSubId);
    }

    return null;
}
```

**New Code:**
```php
private function findProductPlanForSubscription(OfficeGuySubscription $sumitSub): ?ProductPlan
{
    // Priority 1: Match by product_plan_id from metadata
    $productPlanId = data_get($sumitSub, 'metadata.product_plan_id');
    if ($productPlanId !== null) {
        return ProductPlan::whereKey($productPlanId)
            ->where('is_active', true)
            ->first();
    }

    // Priority 2: Match by product_plan_sku from metadata
    $productPlanSku = data_get($sumitSub, 'metadata.product_plan_sku');
    if ($productPlanSku !== null) {
        return ProductPlan::where('sku', $productPlanSku)
            ->where('is_active', true)
            ->first();
    }

    // Priority 3: Match by sumit_sku from metadata
    $sumitSku = data_get($sumitSub, 'metadata.sumit_sku');
    if ($sumitSku !== null) {
        return ProductPlan::where('sku', $sumitSku)
            ->where('is_active', true)
            ->first();
    }

    // No match found - do not rely on name or amount matching
    return null;
}
```

- [ ] **Step 1: Write failing test**

```php
// tests/Unit/Services/SubscriptionSyncServiceTest.php

use App\Models\ProductPlan;
use App\Services\FeatureResolver;
use App\Services\SubscriptionSyncService;
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;

public function test_find_product_plan_matches_by_product_plan_sku(): void
{
    $featureResolver = $this->createMock(FeatureResolver::class);
    $service = new SubscriptionSyncService($featureResolver);

    $productPlan = ProductPlan::factory()->create([
        'sku' => 'PRO-MONTHLY-001',
        'name' => 'Pro Plan',
        'is_active' => true,
    ]);

    // Use OfficeGuySubscription object (not array)
    $sumitSub = new OfficeGuySubscription([
        'name' => 'Pro Plan',
        'amount' => 99.00,
        'metadata' => [
            'product_plan_sku' => 'PRO-MONTHLY-001',
        ],
    ]);

    $result = $service->findProductPlanForSubscription($sumitSub);

    $this->assertNotNull($result);
    $this->assertEquals($productPlan->id, $result->id);
}

public function test_find_product_plan_matches_by_sumit_sku(): void
{
    $featureResolver = $this->createMock(FeatureResolver::class);
    $service = new SubscriptionSyncService($featureResolver);

    $productPlan = ProductPlan::factory()->create([
        'sku' => 'AI-VOICE-AGENT_GROWTH',
        'name' => 'Growth Plan',
        'is_active' => true,
    ]);

    // Subscription created in SUMIT directly (has sumit_sku, not product_plan_sku)
    $sumitSub = new OfficeGuySubscription([
        'name' => 'Growth Plan',
        'amount' => 149.00,
        'metadata' => [
            'sumit_sku' => 'AI-VOICE-AGENT_GROWTH',
        ],
    ]);

    $result = $service->findProductPlanForSubscription($sumitSub);

    $this->assertNotNull($result);
    $this->assertEquals($productPlan->id, $result->id);
}

public function test_find_product_plan_returns_null_when_no_match(): void
{
    $featureResolver = $this->createMock(FeatureResolver::class);
    $service = new SubscriptionSyncService($featureResolver);

    // Subscription with no matching SKU
    $sumitSub = new OfficeGuySubscription([
        'name' => 'Unknown Plan',
        'amount' => 99.00,
        'metadata' => [], // No IDs, no SKUs
    ]);

    $result = $service->findProductPlanForSubscription($sumitSub);

    // Should return null (not fallback to name/amount matching)
    $this->assertNull($result);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/SubscriptionSyncServiceTest.php -v`
Expected: FAIL - tests expect SKU matching that doesn't exist yet

- [ ] **Step 3: Write minimal implementation**

Edit `app/Services/SubscriptionSyncService.php` lines 163-181

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/SubscriptionSyncServiceTest.php -v`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/SubscriptionSyncService.php tests/Unit/Services/SubscriptionSyncServiceTest.php
git commit -m "feat(subscriptions): add SKU matching in findProductPlanForSubscription"
```

---

### Task 4: Integration Testing

**Files:**
- Create: `tests/Feature/Billing/SubscriptionSyncIntegrationTest.php`

- [ ] **Step 1: Write integration test**

```php
// tests/Feature/Billing/SubscriptionSyncIntegrationTest.php

use App\Models\Account;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPrice;
use App\Services\SubscriptionSyncService;
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;

class SubscriptionSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_from_sumit_creates_subscription_with_sku_matching(): void
    {
        // Setup: Create product plan with SKU
        $product = Product::factory()->create();
        $productPlan = ProductPlan::factory()->create([
            'product_id' => $product->id,
            'sku' => 'AI-VOICE-AGENT_GROWTH',
            'name' => 'Growth Plan',
            'is_active' => true,
        ]);
        $productPrice = ProductPrice::factory()->create([
            'product_plan_id' => $productPlan->id,
            'amount' => 14900,
            'currency' => 'ILS',
        ]);

        // Setup: Create account
        $account = Account::factory()->create();

        // Simulate: Create OfficeGuy subscription from SUMIT sync
        $ogSubscription = OfficeGuySubscription::create([
            'subscriber_type' => Account::class,
            'subscriber_id' => $account->id,
            'name' => 'Growth Plan',
            'amount' => 149.00,
            'currency' => 'ILS',
            'status' => 'active',
            'metadata' => [
                'sumit_sku' => 'AI-VOICE-AGENT_GROWTH',  // ◄--- SKU from SUMIT
            ],
        ]);

        // Act: Sync subscriptions - CORRECT METHOD NAME
        $service = app(SubscriptionSyncService::class);
        $result = $service->syncAccountSubscriptions($account);

        // Assert: Should create AccountSubscription linked to ProductPlan
        $this->assertGreaterThan(0, $result['synced']);

        $localSub = AccountSubscription::where('account_id', $account->id)
            ->where('product_plan_id', $productPlan->id)
            ->first();

        $this->assertNotNull($localSub);
        $this->assertEquals($productPlan->id, $localSub->product_plan_id);
    }
}
```

- [ ] **Step 2: Run integration test**

Run: `php artisan test tests/Feature/Billing/SubscriptionSyncIntegrationTest.php -v`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Billing/SubscriptionSyncIntegrationTest.php
git commit -m "test(integration): add SKU-based subscription sync test"
```

---

## Verification Checklist
- [x] Verify ProductPlan has SKU field in database (confirmed: unique index exists)
- [ ] Create test subscription via app with SKU
- [ ] Verify SUMIT receives correct SKU in Items
- [ ] Verify sync matches by SKU
- [ ] Run full test suite
- [ ] Create PR with implementation summary

---

## Key Design Decisions

1. **SKU as Integration Contract**: SKU is treated as the stable identifier between systems
2. **No Name Matching**: Removed unreliable name LIKE matching
3. **No Amount/Currency Fallback**: Removed amount-based matching as it can match wrong plans
4. **Matching Priority**: product_plan_id → product_plan_sku → sumit_sku → null
5. **Active Plans Only**: All queries include `where('is_active', true)` filter
