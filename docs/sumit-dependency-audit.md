# SUMIT Package Dependency Audit

## Purpose

Validate that `PaymentService::processCharge()` (used by our SumitPaymentGateway in redirect mode) has no hidden dependency on package tables that could cause runtime failure in production.

---

## Classes Inspected

| Class | Path | Purpose |
|-------|------|---------|
| `OfficeGuy\LaravelSumitGateway\Services\PaymentService` | vendor/.../PaymentService.php | `processCharge()`, `buildChargeRequest()`, `getOrderCustomer()` |
| `OfficeGuy\LaravelSumitGateway\Services\SettingsService` | vendor/.../SettingsService.php | Used by `getOrderCustomer()` for `merge_customers` |
| `OfficeGuy\LaravelSumitGateway\Models\OfficeGuyTransaction` | vendor/.../OfficeGuyTransaction.php | Referenced in `processCharge()` for non-redirect flow |
| `OfficeGuy\LaravelSumitGateway\Models\OfficeGuySetting` | vendor/.../OfficeGuySetting.php | Used by SettingsService when table exists |

---

## A) Does processCharge() Depend on DB?

### Call path (our usage: redirect mode only)

1. **processCharge($payable, 1, false, true, ...)**  
   - `redirectMode = true` → we use `/billing/payments/beginredirect/`.

2. **buildChargeRequest()**  
   - Calls `getOrderCustomer($order)`.

3. **getOrderCustomer()** (lines 458–537)  
   - Calls `app(SettingsService::class)->get('merge_customers', false)`.
   - If order implements `client()` (our EventBillingPayable does not), it would call `$order->client()->first()` (our adapter is not an Eloquent model with that relation).

4. **SettingsService::get('merge_customers', $default)**  
   - If `Schema::hasTable('officeguy_settings')` is **false**: returns `config('officeguy.merge_customers', $default)` — **no DB access**.
   - If table exists: tries `OfficeGuySetting::get($key)`; on exception falls back to config.

5. **Redirect path exit**  
   - When `$redirectMode === true`, processCharge returns at lines 945–958 with `redirect_url` and **never** reaches:
     - `OfficeGuyTransaction::create(...)` (lines 978–1002),
     - or any event dispatch that writes to package tables.

### Conclusion: DB access in our flow

| Table / component | Required in our flow? | Notes |
|--------------------|------------------------|--------|
| **officeguy_settings** | **No** | SettingsService falls back to config when table does not exist. We only need `merge_customers`; config key `officeguy.merge_customers` is sufficient. |
| **officeguy_transactions** | **No** | Only written when redirect mode is **false** and payment succeeds. We always use redirect mode and never reach that block. |
| **Any other package table** | **No** | Our EventBillingPayable has no `client()` relation; no package model is read or written in the redirect path. |

---

## B) Tables Required

For **our** headless, redirect-only usage of `PaymentService::processCharge()`:

- **No package tables are required.**  
- Config (`config/officeguy.php` / `.env`) is sufficient: at least `company_id`, `private_key`, and for SettingsService fallback the optional `merge_customers` (default false).

---

## C) Current State

- **kalfa_rsvp** (RSVP app DB): Does **not** contain `officeguy_settings` or `officeguy_transactions` (by design; we skip package migrations that depend on OfficeGuy tables).
- **Runtime**: With gateway=sumit we only call processCharge in redirect mode. No package DB reads or writes occur in this path.

---

## Risk Classification

**NONE**

- No dependency on `officeguy_settings` or `officeguy_transactions` in the code path we use.
- SettingsService degrades safely to config when the settings table is missing.
- No risk of runtime failure due to missing package tables in production for this flow.

---

## Recommendation

- **No change required** for package table dependencies.
- Do **not** run package migrations that create `officeguy_settings` / `officeguy_transactions` for the RSVP app DB unless you later adopt non-redirect or package UI flows.
- Keep documenting that headless redirect-only usage relies on config only (and optional env for `merge_customers`).
