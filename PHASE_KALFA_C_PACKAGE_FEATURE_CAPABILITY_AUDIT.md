# PHASE KALFA-C — Package/Feature Capability Audit

**Scope:** Entire repo excluding `vendor/`.  
**Objective:** Verify whether any package/feature/subscription management already exists in the host application.  
**No design proposals.**

---

## 1) Does a package system exist?

**NO.**

- No model in `app/Models` named or containing: Package, Feature, Addon, Module, Capability.  
  (Glob search: `*Subscription*`, `*Package*`, `*Feature*` in `app/Models` → 0 files.)
- No host table names or migrations that define a “packages” or “features” table.
- **Evidence:**  
  - `app/Models/` — only: User, Organization, OrganizationUser, Event, EventBilling, Payment, Plan, Guest, Invitation, RsvpResponse, EventTable, SeatAssignment, BillingWebhookEvent, SystemAuditLog.  
  - `database/migrations/` — no `create_*package*`, `create_*feature*`, `create_*addon*`, `create_*module*`, `create_*capability*` tables.

---

## 2) Does a subscription system exist?

**NO.**

- No host model named Subscription (or containing “Subscription”) in `app/Models`.
- No host table created for storing subscription state (e.g. no `subscriptions` table in host migrations).  
  Migrations that mention `subscription_id` reference the table `officeguy_subscriptions` (vendor/package table), not a host-owned subscription entity:
  - `database/migrations/2025_01_01_000009_create_sumit_incoming_webhooks_table.php:57,82-84` — FK to `officeguy_subscriptions`.
  - `database/migrations/2025_01_01_000008_create_webhook_events_table.php:40,71-73` — FK to `officeguy_subscriptions`.
- Subscription-related behavior in the host is limited to:
  - **Stub service:** `app/Services/OfficeGuy/SystemBillingService.php`  
    - `getOrganizationSubscription(Organization $organization): ?object` → returns `null` (line 22).  
    - `cancelSubscription(Organization $organization): bool` → returns `false` (line 31).  
    - `getActiveSubscriptions(): array` → returns `[]` (line 76).
  - **UI that consumes stubs:**  
    - `app/Livewire/System/Dashboard.php:40` — `$activeSubscriptions = $billing->getActiveSubscriptions();` passed to view (line 63).  
    - `resources/views/livewire/system/dashboard.blade.php:58` — displays “Active subscriptions:” count (always 0 from stub).  
    - `resources/views/livewire/system/organizations/show.blade.php:48-50` — “Plan / Subscription” and “Subscription status: —” as placeholders; no binding to real data.

**Evidence (file:line):**

- `app/Services/OfficeGuy/SystemBillingService.php:19-22` (getOrganizationSubscription returns null).
- `app/Services/OfficeGuy/SystemBillingService.php:27-31` (cancelSubscription returns false).
- `app/Services/OfficeGuy/SystemBillingService.php:75-78` (getActiveSubscriptions returns []).
- `app/Livewire/System/Dashboard.php:38-40,63` (billing service, getActiveSubscriptions, passed to view).
- `resources/views/livewire/system/dashboard.blade.php:58` (Active subscriptions display).
- `resources/views/livewire/system/organizations/show.blade.php:48-50` (Plan/Subscription placeholder).
- `database/migrations/2025_01_01_000009_create_sumit_incoming_webhooks_table.php:57,82-84` (subscription_id → officeguy_subscriptions).
- `database/migrations/2025_01_01_000008_create_webhook_events_table.php:40,71-73` (subscription_id → officeguy_subscriptions).

---

## 3) Does feature gating exist?

**NO.**

- No logic in the host that gates access or capability by subscription, package, feature flag, usage, or quota.
- No middleware, policy, or controller code that checks “limits”, “usage”, “quota”, or “credits” to allow/deny an action.
- **Plan.limits:**  
  - `app/Models/Plan.php:16,24` — `limits` is in fillable and cast to array.  
  - `database/migrations/2026_03_01_100008_create_plans_table.php:18` — `$table->json('limits')->nullable();`  
  - Plan is used only for `plan_id` and `price_cents` (e.g. `app/Http/Controllers/Api/CheckoutController.php:31`, `app/Services/BillingService.php`). There is no read of `$plan->limits` or use of “limits” for gating anywhere in `app/`.
- No host tables for usage/limits (e.g. no columns or tables named sms_count, ai_calls, credits, quota, usage_limit, usage_count) in host migrations or models.  
  (The only “credits” references in the repo are under `resources/lang/vendor/officeguy/`, i.e. vendor, not host.)

**Evidence (file:line):**

- `app/Models/Plan.php:16,24` (limits fillable/casts only).
- `database/migrations/2026_03_01_100008_create_plans_table.php:18` (limits column).
- `app/Http/Controllers/Api/CheckoutController.php:31` (Plan used for findOrFail and plan_id/price; no limits check).
- Grep in `app/` for “limits”, “usage”, “quota”, “credits” in application logic — no feature-gating or usage-enforcement code; only Plan fillable/casts and comment in BillingService.

---

## 4) Summary table

| Question                     | Answer | Note |
|-----------------------------|--------|------|
| Package system (host)       | **NO** | No Package/Feature/Addon/Module/Capability models or tables. |
| Subscription system (host)  | **NO** | Stub service + placeholder UI; FKs only to vendor `officeguy_subscriptions`. |
| Feature gating              | **NO** | No logic that gates by subscription, package, limits, usage, or quota. |

---

## 5) Evidence index (host only)

| Item | Location |
|------|----------|
| Models in app/Models | No Subscription, Package, Feature, Addon, Module, Capability. |
| SystemBillingService (stub) | `app/Services/OfficeGuy/SystemBillingService.php:19-22, 27-31, 75-78` |
| Dashboard subscriptions display | `app/Livewire/System/Dashboard.php:38-40,63`; `resources/views/livewire/system/dashboard.blade.php:58` |
| Org show Plan/Subscription placeholder | `resources/views/livewire/system/organizations/show.blade.php:48-50` |
| Plan.limits (storage only) | `app/Models/Plan.php:16,24`; `database/migrations/2026_03_01_100008_create_plans_table.php:18` |
| subscription_id in migrations | `database/migrations/2025_01_01_000009_create_sumit_incoming_webhooks_table.php:57,82-84`; `2025_01_01_000008_create_webhook_events_table.php:40,71-73` (FK to officeguy_subscriptions) |
