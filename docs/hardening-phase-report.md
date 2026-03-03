# Hardening Phase Report — RSVP + Seating MVP

**Phase:** Production Readiness (stabilization only — no new features)  
**Date:** 2026-03-01

---

## 1. What Was Audited

### 1.1 Database Integrity

- **events.slug:** Verified `unique(['organization_id', 'slug'])` in migration `2026_03_01_100002_create_events_table.php`. Correct for multi-tenant.
- **guests.event_id:** Verified `constrained()->cascadeOnDelete()`. Correct.
- **seat_assignments.guest_id:** Verified `constrained()->cascadeOnDelete()`. Correct.
- **events_billing.event_id:** Previously corrected via migration `2026_03_01_120000_events_billing_event_id_restrict_on_delete.php` — FK is `restrictOnDelete()` (history preserved).
- **payments.organization_id:** Audited; was `cascadeOnDelete()`. Requirement: payments must never cascade-delete (preserve history). **Fixed** (see §2).

### 1.2 Billing Transaction Safety

- **BillingService::initiateEventPayment:** Audited — fully wrapped in `DB::transaction()`. Verified.
- **BillingService::markPaymentSucceeded:** Audited — fully wrapped in `DB::transaction()`. Verified.
- **BillingService::markPaymentFailed:** Audited — fully wrapped in `DB::transaction()`. Verified.
- **Event status transitions:** Searched codebase. Event status is set only in:
  - `BillingService`: draft → pending_payment (initiateEventPayment), pending_payment → active (markPaymentSucceeded).
  - `EventController::store`: new event created with status Draft.
  - No other code mutates event status. Verified.

### 1.3 Webhook Idempotency

- **payments.gateway_transaction_id:** Verified UNIQUE in migration `2026_03_01_100010_create_payments_table.php`. Database layer prevents duplicate transaction IDs.
- **WebhookController:** Checks `Payment::where('gateway_transaction_id', $transactionId)->whereIn('status', ['succeeded', 'failed'])->exists()` before processing. Returns 200 "Already processed" when already handled. Application-layer idempotency verified.
- **billing_webhook_events:** Every webhook request creates a row (source, event_type, payload). Verified.
- **processed_at:** Updated only after successful handling (after `handleWebhook()` returns without exception). If handler throws, response is 500 and `processed_at` is not set. Verified.

### 1.4 Rate Limiting

- **Public RSVP:** Previously used inline `throttle:60,1` and `throttle:120,1`. Audited and replaced with named limiters (see §2).

### 1.5 Authorization

- **CheckoutController::initiate:** Verified `$this->authorize('initiatePayment', $event)`. EventPolicy::initiatePayment delegates to OrganizationPolicy::initiateBilling. Verified.
- **EventController, GuestController, EventTableController, SeatAssignmentController, InvitationController, OrganizationController:** All use `$this->authorize(...)` with appropriate policy. No direct queries without organization scoping; event/guest/resources are resolved via route binding and policy checks organization membership. Verified.
- **PublicRsvpController:** Public by design (slug-based); no org scope required. Verified.

### 1.6 Plan Seeder

- **PlanSeeder:** Audited. Single `per_event` plan (`per-event-basic`), realistic price (99.00 ILS), required fields (name, slug, type, price_cents). No subscription logic. Verified.

---

## 2. What Was Modified

| Item | Change |
|------|--------|
| **payments.organization_id FK** | New migration `2026_03_01_130000_payments_organization_id_restrict_on_delete.php`: drop cascade, add `restrictOnDelete()`. Organization cannot be deleted while it has payments (history preserved). |
| **Rate limiting** | `AppServiceProvider::boot()`: registered named limiters `rsvp_show` (60/min), `rsvp_submit` (10/min), `webhooks` (120/min). `routes/api.php`: RSVP and webhook routes now use `throttle:rsvp_show`, `throttle:rsvp_submit`, `throttle:webhooks`. |
| **WebhookController** | Removed unused `use Illuminate\Support\Facades\DB`. No behavior change. |

---

## 3. What Was Verified (No Change Required)

- events.slug unique per organization.
- guests and seat_assignments cascade on delete as specified.
- events_billing.event_id restrict on delete (existing migration).
- BillingService all three methods in DB::transaction.
- Event status transitions only in BillingService and EventController (create).
- payments.gateway_transaction_id UNIQUE; webhook checks before process; billing_webhook_events logs payload; processed_at only after success.
- CheckoutController authorizes initiatePayment; all API controllers use policies; no cross-organization leakage.
- PlanSeeder minimal per_event plan.

---

## 4. What Remains Future Risk

- **Real gateway integration:** When connecting a real payment gateway (e.g. SUMIT), webhook signature verification and idempotency by gateway_transaction_id must remain; no change to double-processing logic.
- **Organization deletion:** With payments and events_billing now restrict-on-delete, deleting an organization that has billing/payment history will fail until business rules are defined (e.g. soft-delete org, or explicit “archive” flow).
- **Rate limits:** Current limits (10/min for RSVP submit, 120/min for webhooks) are configurable in `AppServiceProvider`; tune per environment if needed.

---

## 5. Production Readiness Status

**Verdict:** **Ready for production hardening gate (stabilization phase).**

- Data integrity: enforced (unique slug per org, FKs with correct onDelete).
- Billing: atomic (transactions), event status only via BillingService.
- Idempotency: application and DB layer (unique gateway_transaction_id, early return when already processed).
- Rate limiting: configured via Laravel rate limiter (named limits).
- Authorization: policies and authorize() on all relevant endpoints; no cross-org leakage identified.
- No new features, no schema changes except FK correctness (payments, events_billing), no new packages, no business logic change except hardening.

---

## 6. DB Changes

| Migration | Purpose |
|-----------|---------|
| `2026_03_01_130000_payments_organization_id_restrict_on_delete.php` | Change `payments.organization_id` FK from `ON DELETE CASCADE` to `ON DELETE RESTRICT` so payment history is never deleted with organization. |

No other schema changes. Existing migration `2026_03_01_120000_events_billing_event_id_restrict_on_delete.php` already enforces restrict for events_billing.event_id.

---

## 7. Security Changes

- **Rate limiting:** Public RSVP GET 60/min, POST 10/min; webhooks 120/min. Reduces abuse and brute-force surface.
- **Authorization:** Confirmed all protected endpoints use policies; CheckoutController explicitly authorizes `initiatePayment` on event.
- **No removal of existing functionality:** All existing auth and validation retained.

---

## 8. Idempotency Status

- **Webhook:** Idempotent. Duplicate webhooks with same `gateway_transaction_id` (and status succeeded/failed) return 200 "Already processed" without re-running logic. Every payload stored in `billing_webhook_events`; `processed_at` set only after successful handling.
- **Database:** UNIQUE on `payments.gateway_transaction_id` prevents duplicate payment rows for same transaction ID.

---

## 9. Transaction Guarantees

- **BillingService::initiateEventPayment:** Single `DB::transaction()` wraps: event status update, EventBilling create, Payment create, gateway call, payment update. Partial state is rolled back on failure.
- **BillingService::markPaymentSucceeded:** Single `DB::transaction()` wraps: payment status update, EventBilling update, event status → active. Atomic.
- **BillingService::markPaymentFailed:** Single `DB::transaction()` wraps payment status update. Atomic.

---

## 10. Rate Limit Configuration

Defined in `App\Providers\AppServiceProvider::boot()`:

| Name | Limit | Used by |
|------|--------|---------|
| `rsvp_show` | 60 per minute | `GET /api/rsvp/{slug}` |
| `rsvp_submit` | 10 per minute | `POST /api/rsvp/{slug}/responses` |
| `webhooks` | 120 per minute | `POST /api/webhooks/{gateway}` |

Applied in `routes/api.php` via `throttle:rsvp_show`, `throttle:rsvp_submit`, `throttle:webhooks`.

---

*End of Hardening Phase Report.*
