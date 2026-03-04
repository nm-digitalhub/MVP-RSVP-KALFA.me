# Panel UI ↔ Account & Entitlements — Connection Map

This document connects **Panel UI structure** ([PANEL_UI_STRUCTURE.md](PANEL_UI_STRUCTURE.md)) to **Account and entitlements** ([ACCOUNT_INSERTION_MAP.md](ACCOUNT_INSERTION_MAP.md), [ACCOUNT_ENTITLEMENTS_README.md](ACCOUNT_ENTITLEMENTS_README.md)), **DB schema** ([DB_SCHEMA_AUDIT.md](DB_SCHEMA_AUDIT.md)), **vendor contract** ([VENDOR_CONTRACT_REQUIREMENTS.md](VENDOR_CONTRACT_REQUIREMENTS.md)), and **compatibility** ([COMPATIBILITY_CHECKLIST_ACCOUNT_PHASE.md](COMPATIBILITY_CHECKLIST_ACCOUNT_PHASE.md)).

---

## 1. Current state: what the panel touches today

### 1.1 Billable subject in the UI

Per **ACCOUNT_INSERTION_MAP** and **COMPATIBILITY_CHECKLIST**: the **billable subject is Organization**. No UI reads or displays `account_id`; no UI creates or edits Account, entitlements, or billing intents.

| Panel area | Route / view | Domain entities used | Account/entitlements in UI? |
|------------|--------------|----------------------|-----------------------------|
| **Tenant — Dashboard** | `/dashboard` → Livewire `Dashboard` | `auth()->user()->currentOrganization()`, org’s events | No |
| **Tenant — Organizations** | `/organizations`, `/organizations/create` → Livewire `Organizations\Index`, `Create` | Organization list/create; org switch POST | No (org only) |
| **Tenant — Profile** | `/profile` → Livewire profile forms | User | No |
| **Tenant — Events** | `dashboard/events`, `dashboard/events/{event}` → Controller views | Organization (via context), Event, guests | No |
| **Checkout (tokenize/status)** | `checkout/{org}/{event}`, `checkout/status/{payment}` | Organization, Event, Payment, EventBilling (via API/BillingService) | No |
| **System — Dashboard** | `/system/dashboard` → Livewire `System\Dashboard` | Aggregates: Organization, User, Event, Guest; SystemBillingService (stub) | No |
| **System — Organizations** | `/system/organizations`, `/system/organizations/{org}` → Livewire `System\Organizations\*` | Organization, suspend/transfer/delete | No |
| **System — Users** | `/system/users`, `/system/users/{user}` → Livewire `System\Users\*` | User, admin toggle | No |
| **Navbar** | `dynamic-navbar` | User, current organization (for switcher), impersonation session | No |

So: **every place that today deals with “who pays” or “what org” uses Organization only.** Checkout and billing flows do not reference Account (per COMPATIBILITY_CHECKLIST).

---

## 2. DB and backend (no panel dependency)

- **DB_SCHEMA_AUDIT:** `organizations` (and optionally `events_billing`, `payments`) have nullable `account_id`; `accounts`, `products`, `product_entitlements`, `account_entitlements`, `account_feature_usage`, `billing_intents` exist. None of these are shown or edited in the current panel.
- **ACCOUNT_INSERTION_MAP:** Account is an optional overlay; Organization remains the customer for billing; creation flows (EventBilling, Payment) do not set `account_id`.
- **ACCOUNT_ENTITLEMENTS_README:** Account holds optional `sumit_customer_id` and type (organization | individual); entitlements and usage tables are for future use; no enforcement or UI.
- **VENDOR_CONTRACT_REQUIREMENTS:** Gateway “customer” is still Organization in config; Payable adapter uses EventBilling → Organization. Account can later hold `sumit_customer_id` without the panel having to change until we add Account/entitlement UI.
- **COMPATIBILITY_CHECKLIST:** Confirms no billing/checkout/panel logic was changed; only additive columns and relations.

---

## 3. Future panel insertion points (when adding Account/entitlements UI)

When you **do** add UI for Account or entitlements, the following connection map shows where it would plug into the existing panel. No such UI exists today.

| Panel area | Possible future use | Related docs |
|------------|---------------------|---------------|
| **Tenant — Organizations list/create** | Optionally show or attach “Account” to an org (e.g. “Billing account”); create/link Account when creating org. | ACCOUNT_INSERTION_MAP (§3: organizations.account_id); ACCOUNT_ENTITLEMENTS_README (Organization may optionally belong to Account). |
| **Tenant — Dashboard** | Show entitlements or usage for current org’s account (if org has account_id); e.g. “Your plan” or “Usage this month.” | ACCOUNT_ENTITLEMENTS_README (account_entitlements, account_feature_usage); DB_SCHEMA_AUDIT (new tables). |
| **Tenant — Profile** | If “individual” accounts are introduced: link user to Account (e.g. owner_user_id); show “My account” or “My entitlements.” | ACCOUNT_ENTITLEMENTS_README (Account type: organization \| individual; owner_user_id). |
| **System — Organizations show** | Show org’s Account (if any), `sumit_customer_id`, or entitlements; optionally create/link Account, manage entitlements. | ACCOUNT_INSERTION_MAP; VENDOR_CONTRACT (sumit_customer_id on customer model); DB_SCHEMA_AUDIT. |
| **System — Dashboard** | High-level metrics for “accounts” or “entitlements” (e.g. count of accounts, active entitlements). | ACCOUNT_ENTITLEMENTS_README; DB (accounts, account_entitlements). |
| **Checkout / billing** | Today: no change. Later: if billing is ever driven by Account (e.g. resolve SUMIT customer from Account), checkout could still be entered from tenant Events; backend would resolve org → account. | ACCOUNT_INSERTION_MAP (§4: SUMIT customer via Account); COMPATIBILITY_CHECKLIST (current checkout unchanged). |

**Panel pattern to reuse:** Same as PANEL_UI_STRUCTURE: tenant pages under `pages/*` or profile with `<x-page-header>` + Livewire; system pages under `system/*` with `@livewire('system.*')`. New routes (e.g. `account` or `entitlements`) would follow the same layout and navbar; no need to change existing routes for compatibility.

---

## 4. Cross-reference summary

| Topic | Primary doc | Panel relevance |
|-------|-------------|-----------------|
| Layouts, nav, tenant vs system routes/views | PANEL_UI_STRUCTURE | Defines where any future Account/entitlement UI would live (tenant vs system, which layout, which pattern). |
| Who is the billable subject; where Account was added without breaking flows | ACCOUNT_INSERTION_MAP | Panel today only touches Organization (and User, Event, Payment); no panel code depends on Account. |
| New tables and how they relate to Organization / Event / Plan | ACCOUNT_ENTITLEMENTS_README | No panel UI yet; when added, tenant “dashboard” or “organizations” and system “organizations” are natural places. |
| Tables and columns (organizations, events_billing, payments, accounts, entitlements) | DB_SCHEMA_AUDIT | Backend/schema source of truth; panel does not yet read account_id or entitlement tables. |
| SUMIT customer/payable contract; what Organization (or future Account) must satisfy | VENDOR_CONTRACT_REQUIREMENTS | Panel does not configure gateway; when Account is used as SUMIT customer, config and adapter change; panel may later show “SUMIT customer” or account status. |
| Proof that billing/checkout and panel behavior are unchanged | COMPATIBILITY_CHECKLIST | Panel flows (dashboard, organizations, checkout, system) unchanged; no new UI for Account or entitlements. |

---

## 5. Summary

- **Panel UI** (PANEL_UI_STRUCTURE) currently uses **Organization** (and User, Event, Payment) only; **no Account or entitlements** appear in any route or view.
- **Account and entitlements** (ACCOUNT_INSERTION_MAP, ACCOUNT_ENTITLEMENTS_README, DB_SCHEMA_AUDIT) are **additive backend/schema only**; compatibility (COMPATIBILITY_CHECKLIST) and vendor contract (VENDOR_CONTRACT_REQUIREMENTS) are unchanged for the panel and checkout.
- **Future panel work:** Attach to existing tenant (organizations, dashboard, profile) and system (organizations show, system dashboard) entry points using the same page pattern and layout; no need to change existing panel or billing behavior when adding Account/entitlement UI later.
