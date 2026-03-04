# PHASE KALFA — Deep Vendor-Level Contract Forensics

**Date:** 2025-03-03  
**Scope:** Evidence-backed compatibility between kalfa.me/httpdocs domain models and `vendor/officeguy/laravel-sumit-gateway`.  
**Rule:** No changes. Investigation only. If 100% alignment is not proven → FAIL.

---

## 1) Effective Runtime Config

**Config/cache:** `php artisan config:clear` and `php artisan cache:clear` were run before extraction.

**Effective Runtime Config (single JSON block):**

```json
{
  "customer_model_class": "App\\Models\\Organization",
  "models.customer": "App\\Models\\Organization",
  "models.order": "App\\Models\\EventBilling",
  "order.model": "App\\Models\\EventBilling",
  "order.resolver": null,
  "staff_model": null
}
```

Extraction method: `config('officeguy')` after config/cache clear; `order.resolver` is `null` (no callable).

---

## 2) Package Semantics From Vendor Code (Authoritative Source)

### A) Customer semantics

**Where customer_id is stored in package tables:**

| Location | Evidence |
|----------|----------|
| `OfficeGuyDocument` | `vendor/officeguy/laravel-sumit-gateway/src/Models/OfficeGuyDocument.php` — `$fillable` includes `'customer_id'` (line 31). Documents store SUMIT customer ID in `customer_id`. |
| `OfficeGuyTransaction` | `vendor/officeguy/laravel-sumit-gateway/src/Models/OfficeGuyTransaction.php` — `$fillable` includes `'customer_id'` (legacy), `'client_id'` (canonical local client), `'sumit_customer_id_used'` (lines 26–28). |
| `SumitWebhook` | `vendor/officeguy/laravel-sumit-gateway/src/Models/SumitWebhook.php` — `$fillable` includes `'customer_id'` (line 35). |
| `WebhookEvent` | `vendor/officeguy/laravel-sumit-gateway/src/Models/WebhookEvent.php` — `$fillable` includes `'customer_id'` (line 40). |

**Where sumit_customer_id is read or queried:**

| File:line | Quote |
|------------|--------|
| `DocumentSyncListener.php:107` | `$customer = $customerModel::where('sumit_customer_id', $customerId)->first();` |
| `SyncAllDocumentsCommand.php:95` | `$query = $customerModel::whereNotNull('sumit_customer_id');` |
| `SyncAllDocumentsCommand.php:158` | Same pattern. |
| `SyncAllDocumentsCommand.php:189` | `(int) $user->sumit_customer_id` |
| `OfficeGuyDocument.php:96` | `return $this->belongsTo($customerModel, 'customer_id', 'sumit_customer_id');` — relationship matches `customer_id` (SUMIT ID) to customer model’s **column** `sumit_customer_id`. |
| `OfficeGuyTransaction.php:250` | `$client = $customerModel::where('sumit_customer_id', $sumitCustomerIdUsed)->first();` |
| `SumitWebhook.php:303` | `$client = $customerModel::where('sumit_customer_id', $customerId)->first();` |
| `CrmDataService.php:927` | `$client = $customerModel::where('sumit_customer_id', $sumitEntityId)->first();` |
| `PaymentService.php:485-486` | `if ($client && !empty($client->sumit_customer_id)) { $sumitCustomerId = $client->sumit_customer_id; }` — expects `$order->client()` and client to have attribute `sumit_customer_id`. |
| `PaymentService.php:1127,1132` | `HasSumitCustomer $customer` and `$sumitCustomerId = $customer->getSumitCustomerId();` |
| `DebtService.php:52` | `$sumitCustomerId = $customer->getSumitCustomerId();` — type hint `HasSumitCustomer`. |
| `DocumentService.php:415` | `$sumitCustomerId = $customer->getSumitCustomerId();` — type hint `HasSumitCustomer`. |
| `HasSumitCustomerTrait.php:44-46` | `public function getSumitCustomerId(): ?int { return $this->sumit_customer_id; }` — default implementation assumes **attribute** `sumit_customer_id`. |
| `CheckSumitDebtJob.php:32,41,44,49,57,62,67,86,107,122` | Queries/filters by `sumit_customer_id` on the customer model. |
| `SubscriptionService.php:450` | `$sumitCustomerId = $subscriber->sumit_customer_id ?? null;` — subscriber must have attribute or accessor. |
| `TokenService.php:192` | `$sumitCustomerId = $owner->sumit_customer_id ?? null;` |
| `OfficeGuyTransactionPolicy.php:40` | `(string) $transaction->customer_id === (string) ($user->sumit_customer_id ?? '')` — compares to **user**’s `sumit_customer_id`. |

**Whether the package requires HasSumitCustomer interface, class-string only, or resolver:**

- **Interface required:** `DebtService::getCustomerBalance(HasSumitCustomer $customer)`, `DocumentService::syncForClient(HasSumitCustomer $customer)`, `PaymentService::processRefund(HasSumitCustomer $customer)` — these **require** an object implementing `HasSumitCustomer` (see `vendor/officeguy/laravel-sumit-gateway/src/Contracts/HasSumitCustomer.php`).
- **Class-string + column:** When the package resolves the customer **model** via `app('officeguy.customer_model')`, it then:
  - Uses `$customerModel::where('sumit_customer_id', $id)->first()` (multiple listeners, commands, models).
  - Uses `OfficeGuyDocument::customer()` as `BelongsTo($customerModel, 'customer_id', 'sumit_customer_id')` — so the **model table** must have a column `sumit_customer_id`.
- **Conclusion:** The package expects the configured customer model either to (1) implement `HasSumitCustomer` (with `getSumitCustomerId()` etc.) and/or (2) have a **column** `sumit_customer_id` for queries and for `OfficeGuyDocument::customer()` / `OfficeGuyTransaction::client()`. The trait `HasSumitCustomerTrait` implements the interface by returning `$this->sumit_customer_id` (attribute). So in practice the package assumes the customer model has a **physical column** `sumit_customer_id` for lookups and relations.

**Customer ID in package context represents:** The SUMIT API customer identifier (integer). It is stored in package tables as `customer_id` (and sometimes `sumit_customer_id_used`). In the host customer model it is stored in the column `sumit_customer_id` and exposed via `HasSumitCustomer::getSumitCustomerId()`.

---

### B) Payable / Order semantics

**Where order_id / order_type are stored:**

- `OfficeGuyTransaction`: `$fillable` includes `'order_id'`, `'order_type'` (lines 19–20). `order()` is `morphTo('order', 'order_type', 'order_id')` (lines 92–95).
- `OfficeGuyDocument`: `$fillable` includes `'order_id'`, `'order_type'` (lines 27–28). `order()` is `morphTo('order', 'order_type', 'order_id')` (lines 61–63).

**Where Payable interface is required:**

| File:line | Evidence |
|-----------|----------|
| `PaymentService.php:839-840` | `public static function processCharge(Payable $order, ...)` — parameter type hint. |
| `PaymentService.php:458` | `getOrderCustomer(Payable $order, ...)` |
| `PaymentService.php:82-83` | `getOrderVatRate(Payable $order)` |
| `PaymentService.php:574` | `getPaymentOrderItems(Payable $order)` |
| `FulfillmentListener.php:103` | `if (! $event->payable instanceof Payable)` — runtime check. |
| `FulfillmentDispatcher.php:92` | `public function dispatch(Payable $payable, ...)` |
| `ResolvedPaymentIntent.php:29` | `public Payable $payable` |
| `CheckoutIntent.php:27` | `public Payable $payable` |
| `OrderRepository.php:36,47,55,64` | `findById(...): ?Payable`, `findByOrderKey(...): ?Payable`, `create/update(..., Payable $order)`. |
| `DonationService.php`, `BitPaymentService.php`, `UpsellService.php`, `MultiVendorPaymentService.php`, `SecureSuccessUrlGenerator.php`, `CheckoutViewResolver.php` | Method signatures or calls use `Payable` type. |

**Where checkout/fulfillment receives the object:**

- Checkout: `CheckoutController.php:32-33` — `$order = OrderResolver::resolve($orderId); if (! $order instanceof \OfficeGuy\LaravelSumitGateway\Contracts\Payable) { ... }`.
- Fulfillment: `FulfillmentListener.php` receives `PaymentCompleted` with `$event->payable`; requires `$event->payable instanceof Payable` (line 103).

**Whether order.model must implement Payable or order.resolver fully overrides:**

- `OrderResolver.php:14-35`: (1) If `order.resolver` is set and callable, it is invoked; if the result `instanceof Payable`, it is returned. (2) Otherwise `order.model` is used: `$model = $modelClass::find($orderId); if ($model instanceof Payable) return $model;` (lines 27–30). So when **resolver is null**, the package expects `order.model` to be a class whose instances **implement Payable**. If the model does not implement Payable, `OrderResolver::resolve()` returns **null**.
- `AutoCreateUserListener.php:101-109`: Does **not** use `OrderResolver`. It uses `config('officeguy.order.model')` and `$orderClass::find($orderId)` and then uses the result as a raw object with properties `user_id`, `client_id`, `client_email`, `client_name`, `billing_*`, and `update([...])`. So for this listener, the package expects the **order model** to be an Eloquent model with those attributes/methods, **not** necessarily Payable.

**Adapter (EventBillingPayable) usage:**

- Kalfa instantiates `EventBillingPayable` in `app/Services/SumitPaymentGateway.php` (lines 33, 92) and passes it to `PaymentService::processCharge($payable, ...)`. The package thus receives the **adapter** (Payable), not the raw Eloquent model, for the charge flow. That path is aligned. The package never receives the raw `EventBilling` model in the charge API; it receives a Payable.

**Payable in package context represents:** Any entity that can be charged via SUMIT: it must implement `Payable` (getPayableId, getPayableAmount, getPayableCurrency, getCustomer*, getLineItems, getOrderKey, getPayableType, etc.). The package stores the **payable** identity in `officeguy_transactions.order_id` / `order_type` (polymorphic). When resolving by ID without a custom resolver, the package expects `order.model` to be a class whose instances implement `Payable`.

---

### C) Subscriber semantics

**Where subscriber_id / subscriber_type are used:**

- `vendor/officeguy/laravel-sumit-gateway/src/Models/Subscription.php`: `$fillable` includes `'subscriber_type'`, `'subscriber_id'` (lines 40–41). `subscriber()` is `morphTo()` (lines 83–86). Comment: "Get the subscriber (User/Customer)".

**What is queried for matching subscriptions/documents:**

- `DocumentService.php:646`: `->whereHas('subscriber', function ($q) use ($sumitCustomerId): void { $q->where('sumit_customer_id', $sumitCustomerId); })` — subscriber must be queryable by `sumit_customer_id`.
- `DocumentService.php:823-824`: `$subscriber = $subscription->subscriber; $sumitCustomerId = $subscriber->sumit_customer_id ?? null;`
- `SubscriptionService.php:443-450`: `syncFromSumit(mixed $subscriber, ...)` — "User/Customer model with sumit_customer_id"; `$sumitCustomerId = $subscriber->sumit_customer_id ?? null;`

**Subscriber ID in package context represents:** The polymorphic owner of a subscription (`subscriber_id` + `subscriber_type`). The subscriber is expected to have a `sumit_customer_id` attribute (or equivalent) for document sync and subscription sync. So in package context, **subscriber** is the entity that has a SUMIT customer ID and is linked to subscriptions/documents.

---

## 3) Host Models (Kalfa) — Inspection

### Organization (`App\Models\Organization`)

| Item | Value |
|------|--------|
| File | `app/Models/Organization.php` (lines 1–57) |
| Primary key | Default `id` (not overridden). |
| Table | `organizations` (default). |
| Fillable | `name`, `slug`, `billing_email`, `settings`, `is_suspended` (lines 14–20). |
| sumit_customer_id | **Not** in fillable; not in model. |
| Schema check | `Schema::hasColumn('organizations', 'sumit_customer_id')` → **false**. |
| Interfaces | None. Does not implement `HasSumitCustomer`. |
| Traits | None from package. |
| Morph relations | None. |

**Organization ID represents:** The primary key of the tenant (organization) in Kalfa. It is **not** the SUMIT customer ID; Organization has no `sumit_customer_id` and does not implement `HasSumitCustomer`.

### EventBilling (`App\Models\EventBilling`)

| Item | Value |
|------|--------|
| File | `app/Models/EventBilling.php` (lines 1–53) |
| Primary key | Default `id`. |
| Table | `events_billing` (line 14). |
| Fillable | `organization_id`, `event_id`, `plan_id`, `amount_cents`, `currency`, `status`, `paid_at` (lines 16–24). |
| user_id / client_id | **Not** present. |
| Schema check | `Schema::hasColumn('events_billing', 'user_id')` → **false**; `client_id` → **false**. |
| Interfaces | None. Does not implement `Payable`. |
| Morph | `payments()` → `morphMany(Payment::class, 'payable')`. |

**EventBilling ID represents:** The primary key of the billing record for an event in Kalfa. It is the **payable** identity when wrapped in `EventBillingPayable`. The raw model does **not** implement `Payable` and has no `user_id` or `client_id`.

---

## 4) Cross-Match Matrix

| Concept | Configured Class | What its ID Represents | Package Expects | Evidence (file:line) | Alignment |
|---------|------------------|------------------------|-----------------|----------------------|-----------|
| Customer | `App\Models\Organization` | Tenant PK (organization_id). Not SUMIT customer ID. | Model used with `where('sumit_customer_id', ...)` and `BelongsTo(..., 'customer_id', 'sumit_customer_id')`; optional `HasSumitCustomer`. | DocumentSyncListener.php:107; OfficeGuyDocument.php:96; OfficeGuyTransaction.php:250; HasSumitCustomerTrait.php:44-46 | **FAIL** — Organization has no `sumit_customer_id` column; does not implement HasSumitCustomer. |
| Order/Payable (charge API) | N/A (adapter used) | Kalfa passes `EventBillingPayable` to `processCharge`. | `Payable` type hint; adapter satisfies interface. | PaymentService.php:839-840; SumitPaymentGateway.php:33,92 | **PASS** — Adapter used; no use of order.model for this path. |
| Order (resolve by ID) | `App\Models\EventBilling` | EventBilling PK. | `OrderResolver::resolve()`: model must `instanceof Payable` when resolver is null. EventBilling does not implement Payable. | OrderResolver.php:26-31; CheckoutController.php:32 | **FAIL** — EventBilling::find($id) is not Payable; resolver returns null. |
| Order (AutoCreateUserListener) | `App\Models\EventBilling` | EventBilling PK. | Raw model with `user_id`, `client_id`, `client_email`, `client_name`, `billing_*`, `update([...])`. | AutoCreateUserListener.php:41-82,101-109 | **FAIL** — EventBilling has no user_id, client_id, client_email; would break if listener ran. |
| Staff | `null` | N/A | Optional; used for CRM owner/assigned, policy, guest_user_model fallback. | OfficeGuyServiceProvider.php; INTEGRATION_API_SURFACE.md | **INCOMPLETE** — Not configured; package can skip or use null. |

---

## 5) Final Verdict

**Is integration 100% aligned?** **NO.**

**Reasons:**

1. **Customer (Organization):** The package expects the configured customer model to have a **column** `sumit_customer_id` and/or to implement `HasSumitCustomer`. Organization has neither. Any code path that calls `$customerModel::where('sumit_customer_id', ...)` or uses `OfficeGuyDocument::customer()` / `OfficeGuyTransaction::client()` is **proven mismatch** (vendor citations above).
2. **Order (EventBilling) as model:** When `order.resolver` is null, `OrderResolver::resolve($id)` does `EventBilling::find($id)` and checks `instanceof Payable` → false → returns null. So any package flow that resolves order by ID (checkout UI, callback, webhook resolution) gets null. Furthermore, `AutoCreateUserListener` expects the order **model** to have `user_id`, `client_id`, `client_email`, etc.; EventBilling does not, so that listener would break if triggered.

**Minimal fixes (evidence-based):**

| Fix | Type | Evidence |
|-----|------|----------|
| Customer model | **Migration + optional interface** | Add column `sumit_customer_id` (nullable integer) to `organizations` and optionally implement `HasSumitCustomer` on Organization (e.g. via HasSumitCustomerTrait or manual methods) so that `$customerModel::where('sumit_customer_id', $id)->first()` and `OfficeGuyDocument::customer()` work. |
| Order resolution by ID | **Resolver** | Set `config('officeguy.order.resolver')` to a callable that, given an order ID, loads `EventBilling` and returns `new \App\Services\Sumit\EventBillingPayable(EventBilling::find($id))` (or null). Then `OrderResolver::resolve($id)` returns a Payable. No change to package source. |
| AutoCreateUserListener | **Config or code** | Disable `officeguy.auto_create_guest_user` (config) so the listener does not run, **or** ensure the listener never receives events for EventBilling (e.g. if Kalfa does not use package PaymentCompleted with orderId). If Kalfa does fire PaymentCompleted with orderId and the listener runs, the order model must have user_id/client_id/client_email or the listener must be overridden/disabled. |

No vague language. Every statement above is tied to the cited vendor file and line numbers.
