# Vendor Contract Requirements — officeguy/laravel-sumit-gateway

Authoritative extraction from `vendor/officeguy/laravel-sumit-gateway` for “customer” and “payable” semantics. Citations: file:line. Requirements classified MUST / SHOULD / OPTIONAL.

---

## 1. Customer semantics

### 1.1 Config: customer model

| Key | Purpose | Evidence |
|-----|---------|----------|
| customer_model_class | Resolved customer model class (DB or config) | `config/officeguy.php:106` (vendor); host overrides in `config/officeguy.php:108` |
| officeguy.models.customer | Nested config customer model | `config/officeguy.php` (vendor); host `config/officeguy.php:119` |

Resolution order (vendor): 1) DB `officeguy_settings.customer_model_class`, 2) `config('officeguy.models.customer')`, 3) `config('officeguy.customer_model_class')`.  
`OfficeGuyServiceProvider.php:99-132` — Config::get('officeguy.models.customer'), then config('officeguy.customer_model_class').

### 1.2 HasSumitCustomer interface

**Location:** `src/Contracts/HasSumitCustomer.php`

| Method | Return | MUST/SHOULD |
|--------|--------|-------------|
| getSumitCustomerId() | ?int | **MUST** — “required for all API operations related to this customer” (docblock line 28) |
| getSumitCustomerEmail() | ?string | SHOULD (documents, email) |
| getSumitCustomerName() | ?string | SHOULD (invoices, receipts) |
| getSumitCustomerPhone() | ?string | OPTIONAL |
| getSumitCustomerBusinessId() | ?string | OPTIONAL (tax invoices) |

Trait `HasSumitCustomerTrait` (`src/Support/Traits/HasSumitCustomerTrait.php:44-45`) implements getSumitCustomerId() as `return $this->sumit_customer_id`. So:

- **MUST:** If the package treats the config customer as a SUMIT customer, that model must be able to return a SUMIT customer ID (either implement HasSumitCustomer with getSumitCustomerId(), or the trait assumes attribute **sumit_customer_id**).
- **MUST (for trait):** Attribute `sumit_customer_id` (int nullable) if using HasSumitCustomerTrait.  
  Evidence: `HasSumitCustomerTrait.php:12`, `44-45`.

### 1.3 Where “customer” is used in package

| Usage | File:Line | Expectation |
|-------|-----------|-------------|
| DebtService::getCustomerBalance, getBalanceReport, getPaymentsForCustomer | `DebtService.php:50-52`, `378-380`, `462-466` | Parameter HasSumitCustomer; calls getSumitCustomerId() |
| PaymentService (customer param) | `PaymentService.php:1127-1132` | HasSumitCustomer; getSumitCustomerId() |
| DocumentService::syncForClient, document creation | `DocumentService.php:413-415`, `1008-1013` | HasSumitCustomer; getSumitCustomerId() |
| OfficeGuyTransactionPolicy (user vs transaction customer) | `Policies/OfficeGuyTransactionPolicy.php:40` | Compares `$transaction->customer_id` to `$user->sumit_customer_id` |
| officeguy_transactions.customer_id | `database/migrations/2024_01_01_000001_create_officeguy_transactions_table.php:25` | Stores SUMIT customer ID (string nullable) |

So: **customer** in package = entity that has a SUMIT customer ID (stored or returned via getSumitCustomerId()). Host’s `Organization` is configured as customer but does **not** implement HasSumitCustomer and has **no** sumit_customer_id column — so any code path that calls getSumitCustomerId() on the configured customer model would fail or get null. Current Kalfa flow avoids that by not using those code paths; payment flow uses EventBillingPayable and passes organization_id to gateway; Payable::getCustomerId() returns organization_id (local ID), not SUMIT customer ID.

---

## 2. Payable semantics

### 2.1 Payable interface

**Location:** `src/Contracts/Payable.php`

All methods listed in the interface are part of the contract. Key for resolution and charging:

| Method | Return | MUST/SHOULD |
|--------|--------|-------------|
| getPayableId() | string\|int | **MUST** |
| getPayableAmount() | float | **MUST** |
| getPayableCurrency() | string | **MUST** |
| getCustomerId() | string\|int\|null | **MUST** (can be local ID; package may map to SUMIT customer elsewhere) |
| getCustomerEmail() | ?string | SHOULD |
| getCustomerName() | string | **MUST** |
| getOrderKey() | ?string | **MUST** (webhook validation) |
| getPayableType() | PayableType | **MUST** |
| getLineItems() | array | **MUST** |

Others (address, phone, shipping, fees, VAT, etc.) have default behavior in package or are optional for one-time charge. Evidence: `Payable.php` full interface; `EventBillingPayable.php` implements all required for host’s one-time flow.

### 2.2 Order resolution (order.model / order.resolver)

**Location:** `src/Support/OrderResolver.php:15-36`

1. If `config('officeguy.order.resolver')` is callable: call it with orderId; if result is Payable, return it.
2. Else if `config('officeguy.order.model')` is set: find model by id; if instance of Payable, return it.

So:
- **order.resolver:** Optional. If set, **MUST** return Payable or null. Evidence: `OrderResolver.php:17-22`.
- **order.model:** Optional. If used, the model **MUST** implement Payable (or resolver is used instead). Evidence: `OrderResolver.php:25-31`.

Host config: `config/officeguy.php:291-294` — order.model = `App\Models\EventBilling`, order.resolver = null. EventBilling does **not** implement Payable; the host uses EventBillingPayable adapter. So any package code that uses OrderResolver::resolve() with EventBilling as model would get a model that is not instanceof Payable and would receive null. Kalfa’s checkout does not use OrderResolver: SumitPaymentGateway builds EventBillingPayable from EventBilling and calls PaymentService::processCharge($payable, ...). So current flow is compatible. For any future use of OrderResolver (e.g. webhooks), host would need order.resolver to return EventBillingPayable wrapping EventBilling, or keep not using OrderResolver for this flow.

---

## 3. Subscriber (subscriptions)

**Location:** `database/migrations/2025_01_01_000006_create_subscriptions_table.php:20`

`officeguy_subscriptions` has morphs('subscriber'). Package expects a “subscriber” model (morph target). For subscription features the subscriber typically needs to be resolvable to a SUMIT customer (e.g. subscriber has or is linked to sumit_customer_id). No host table currently has subscriber_type/subscriber_id; this is package-owned. **MUST** (for subscription features): subscriber model should provide or link to SUMIT customer ID when required by package. **OPTIONAL** for this phase: we are not implementing subscription logic.

---

## 4. Must / Should / Optional summary

| Requirement | Level | Note |
|-------------|--------|------|
| Customer model: getSumitCustomerId() or sumit_customer_id | **MUST** (if package customer APIs used) | Host does not use Debt/Document/Subscription customer APIs; one-time charge uses Payable only. So currently not required for existing flow. |
| Payable: getPayableId, getPayableAmount, getPayableCurrency, getCustomerId, getCustomerName, getOrderKey, getPayableType, getLineItems | **MUST** | Host satisfies via EventBillingPayable. |
| order.model implementing Payable | **MUST** if OrderResolver used with model | Host uses resolver=null and does not rely on OrderResolver for checkout; adapter used instead. |
| order.resolver returning Payable | **SHOULD** if package routes use order ID | For webhooks/callbacks that resolve by order ID, resolver returning EventBillingPayable would be correct. |
| Customer model HasSumitCustomer + sumit_customer_id | **SHOULD** for full integration | For document sync, debt, or subscription, Organization (or Account) would need sumit_customer_id and HasSumitCustomer. |

---

## 5. Relevance to Account + Entitlements phase

- We are **not** changing Organization to implement HasSumitCustomer or add sumit_customer_id in this phase.
- We are adding **Account** with optional **sumit_customer_id** and relations so that later either Organization or Account can be wired as the SUMIT customer without changing existing billing/checkout behavior.
- Payable and order resolution remain as today (EventBillingPayable adapter; no change to BillingService or CheckoutController).
