# Account Insertion Map — Evidence-Based Code Audit

Map of the current ownership/billable model and the minimal insertion point for a new Account entity without breaking existing flows. All claims cite file:line.

---

## 1. What is the “billable subject” today?

**Conclusion: Organization is the billable subject.** Evidence:

| Evidence | File:Line | What it shows |
|----------|-----------|----------------|
| EventBilling created with `organization_id` from event | `app/Services/BillingService.php:37-42` | `EventBilling::create(['organization_id' => $event->organization_id, ...])` |
| Payment created with same `organization_id` | `app/Services/BillingService.php:45-50` | `$eventBilling->payments()->create(['organization_id' => $event->organization_id, ...])` |
| Gateway receives `organization_id` for charge | `app/Services/BillingService.php:53-60` | `$this->gateway->createOneTimePayment($event->organization_id, ...)` |
| Token flow same: organization_id on EventBilling + Payment + gateway | `app/Services/BillingService.php:91-109` | Same pattern for `initiateEventPaymentWithToken` |
| EventBillingPayable returns organization_id as getCustomerId() | `app/Services/Sumit/EventBillingPayable.php:64-67` | `return $this->eventBilling->organization_id` |
| EventBillingPayable uses organization for email/name | `app/Services/Sumit/EventBillingPayable.php:37-51` | `organization?->billing_email`, `organization?->name` |
| Checkout API scoped by Organization + Event | `app/Http/Controllers/Api/CheckoutController.php:27` | `initiate(..., Organization $organization, Event $event)` |
| Organization has eventsBilling(), payments() | `app/Models/Organization.php:41-47` | `eventsBilling()`, `payments()` relations |

User is not the billable subject: no Payment or EventBilling created by user_id; gateway is given organization_id. EventBilling is the **payable record** (one per event payment); Organization is the **customer** for SUMIT (conceptually; Organization does not implement HasSumitCustomer or have sumit_customer_id).

---

## 2. Creation flows (who creates what)

| Entity | Created by | File:Line |
|--------|------------|-----------|
| EventBilling | BillingService::initiateEventPayment, initiateEventPaymentWithToken | `BillingService.php:36-42`, `91-98` |
| Payment | BillingService (via EventBilling->payments()) | `BillingService.php:45-50`, `99-105` |
| Event | Dashboard/API (EventController, StoreEventRequest); status set to PendingPayment by BillingService | `BillingService.php:34` |
| Organization | Livewire Organizations/Create, API (OrganizationController) | Not in BillingService; org exists before checkout |

No code path creates EventBilling or Payment without an existing Event and Organization. So the minimal insertion point is **additive**: add an optional Account that can later be linked to Organization (and optionally to EventBilling/Payment) without changing these creation flows.

---

## 3. Minimal insertion point for Account

**Design:** Introduce an `Account` as an optional layer. Organization remains the current “customer” for billing; Account can later become the SUMIT customer holder (e.g. Account has sumit_customer_id) or the entitlement holder.

**Additive only:**

1. **New table `accounts`**  
   No change to existing tables in this step except adding nullable FKs where we want to link.

2. **Nullable `account_id` on tables that today are owned by Organization**  
   - `organizations.account_id` (nullable, FK → accounts.id).  
   - Optionally `events_billing.account_id` (nullable), `payments.account_id` (nullable).  
   Rationale: all current flows use organization_id; adding nullable account_id does not break any existing query or constraint. Business logic continues to use organization_id until a later phase.

3. **No changes to:**  
   - BillingService (no new parameters, no switch from organization_id to account_id).  
   - CheckoutController (still Organization + Event).  
   - SumitPaymentGateway (still receives organization_id; EventBillingPayable still uses eventBilling->organization).  
   - EventBilling or Payment creation (no requirement to set account_id).

**Evidence that this is non-breaking:**

| Check | Evidence |
|-------|----------|
| BillingService never reads account_id | `BillingService.php` — only organization_id, event_id, plan_id, event_billing_id, payment_id |
| CheckoutController never reads account_id | `CheckoutController.php` — authorizes event, gets plan, calls BillingService |
| EventBilling model fillable/relations | `EventBilling.php` — no account_id today; adding nullable account_id + relation does not force any assignment |
| Payment model | `Payment.php` — same |
| Organization model | `Organization.php` — adding belongsTo(Account) and nullable account_id does not require existing rows to have account_id |

---

## 4. Where Account could be used later (out of scope for this phase)

- **SUMIT customer:** If Account holds `sumit_customer_id`, then Organization could delegate “SUMIT customer” to Account (e.g. Organization belongs to Account; gateway resolves customer from Account). Not implemented in this phase.
- **Entitlements:** account_entitlements, account_feature_usage would reference accounts.id. No enforcement in this phase.
- **Billing intents:** billing_intents could reference account_id for future purchase abstraction. No logic change in this phase.

---

## 5. Stop-gate check

- **Is the billable subject clear?** Yes — Organization (evidence §1).
- **Can we add Account without refactoring existing flows?** Yes — add `accounts` table and nullable `account_id` on organizations (and optionally events_billing, payments); do not change any code that creates or reads EventBilling/Payment/Organization. Relations and nullable columns only.
- **Ambiguity about Account as SUMIT “customer”?** For this phase we do not assign SUMIT customer to Account; we only add the Account entity and optional link from Organization. So no conflict: Organization remains the current customer for the gateway; Account is additive for future use.

**Verdict: Proceed with additive infrastructure (Step 2).**
