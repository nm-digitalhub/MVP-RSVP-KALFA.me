# Config Alignment Report — Model & OfficeGuy Reconciliation

**Date:** 2025-03-03  
**Scope:** `app/Models/`, `config/officeguy.php`, `.env.testing`

---

## 1. Detected Domain Entities (from app/Models/)

### Customer entity: **Organization**

- **Evidence:** Billing and payments are scoped to organizations. `EventBilling` belongs to `Organization`; `Payment` belongs to `Organization`. `EventBillingPayable` (Sumit adapter) uses `EventBilling::organization` for `getCustomerEmail()`, `getCustomerName()`, and `getCustomerId()` (returns `organization_id`). The tenant that pays for events is the organization.
- **Models considered:** `User` = authenticated member of org; `Guest` = event invitee (not the payer). Neither is the billing customer in this domain.

### Payable / reservation entity: **EventBilling**

- **Evidence:** `EventBilling` is the record that gets paid (amount_cents, currency, status, paid_at). `Payment` morphs to `payable` (EventBilling). `BillingService` creates `EventBilling` and `Payment`; webhook success updates `EventBilling` status and activates the related `Event`. The package’s Payable adapter is `EventBillingPayable(EventBilling)`; the app does not use an `Order` model.
- **Note:** `Event` is the “reservation/booking” in the product sense; the **payable** (what SUMIT charges) is `EventBilling`.

### Staff / admin entity: **User**

- **Evidence:** Only `User` extends `Authenticatable`. `User` has `is_system_admin` and `is_disabled`. System admin and tenant users are the same model; no separate staff model exists.
- **Config:** `config/officeguy.php` has no `staff_model` (or equivalent). No change added; report documents the inferred staff entity only.

---

## 2. Config Mismatches (before)

| Config key | Previous default | Issue |
|------------|------------------|--------|
| `customer_model_class` | `App\Models\Client` | No `Client` model in project. |
| `models.customer` | `App\Models\Client` | Same. |
| `models.order` | `App\Models\Order` | No `Order` model; payable is `EventBilling`. |
| `order.model` | `App\Models\Order` | Same. |

---

## 3. Config Changes Performed

### config/officeguy.php

- **customer_model_class:** default changed from `App\Models\Client` to `App\Models\Organization`.
- **models.customer:** default changed from `App\Models\Client` to `App\Models\Organization`.
- **models.order:** default changed from `App\Models\Order` to `App\Models\EventBilling`.
- **order.model:** default changed from `App\Models\Order` to `App\Models\EventBilling`.

All remain overridable via env: `OFFICEGUY_CUSTOMER_MODEL_CLASS`, `OFFICEGUY_CUSTOMER_MODEL`, `OFFICEGUY_ORDER_MODEL`.

### .env.testing

- **OFFICEGUY_CUSTOMER_MODEL:** `App\Models\Client` → `App\Models\Organization`.
- **OFFICEGUY_ORDER_MODEL:** `App\Models\Order` → `App\Models\EventBilling`.
- **OFFICEGUY_CUSTOMER_MODEL_CLASS:** `App\Models\Client` → `App\Models\Organization`.
- Removed duplicate `OFFICEGUY_CUSTOMER_MODEL_CLASS` line.

---

## 4. Ambiguity and Risks

- **Package use of `order.model`:** The app does not use `config('officeguy.order')` or `config('officeguy.models.order')`. Payment flow is custom: `SumitPaymentGateway` loads `EventBilling` and wraps it in `EventBillingPayable`; webhooks resolve via `Payment->payable` (morphTo → `EventBilling`). If the package uses `order.model` elsewhere (e.g. Filament, internal resolution), it may expect a model implementing the package’s `Payable` contract. `EventBilling` does **not** implement that contract; the app uses the `EventBillingPayable` adapter. **Risk:** Any package code that instantiates the config model and expects a Payable interface may need a custom `order.resolver` that returns `EventBillingPayable` instances. No such code path was observed in the app; if it appears, set `order.resolver` and leave `order.model` for reference or null.
- **Customer sync/merge:** If OfficeGuy customer merging or local sync is enabled (`customer_merging_enabled` / `customer_local_sync_enabled`), the package may expect the customer model to implement specific methods or schema. `Organization` was not checked against package interfaces; enable only if the package documents compatibility with a custom customer model.
- **Repositories:** `repositories.customer` and `repositories.order` remain `null`. No change; the app does not use them.

---

## 5. Summary

| Concept | Detected model | Config key(s) updated |
|--------|----------------|------------------------|
| Customer | `Organization` | `customer_model_class`, `models.customer` |
| Payable / order | `EventBilling` | `models.order`, `order.model` |
| Staff / admin | `User` (inferred; no key in config) | — |

Package source was not modified. Changes are limited to defaults and `.env.testing` so that config and tests reference existing domain models.
