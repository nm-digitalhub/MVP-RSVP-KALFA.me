# PHASE 8 — Domain Integrity Audit Report
**Event SaaS Hard Isolation**  
*Generated: Post PHASE 7, Pre-Composer*

---

## STEP 1 — Directory Audit (Tree Summary)

*Excluding: vendor/, storage/, node_modules/, .git/*

### app/
```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── Enums/
│   ├── EventBillingStatus.php
│   ├── EventStatus.php
│   ├── InvitationStatus.php
│   ├── OrganizationUserRole.php
│   ├── PaymentStatus.php
│   └── RsvpResponseType.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/ (Checkout, Event, EventTable, Guest, Invitation, Organization, Payment, PublicRsvp, SeatAssignment, Webhook)
│   │   ├── Auth/VerifyEmailController.php
│   │   ├── CheckoutStatusController.php
│   │   ├── CheckoutTokenizeController.php
│   │   ├── Controller.php
│   │   ├── Dashboard/ (Dashboard, Event, OrganizationContext)
│   │   ├── PublicEventController.php
│   │   └── PublicRsvpViewController.php
│   ├── Middleware/EnsureOrganizationSelected.php
│   └── Requests/Api/ (InitiateCheckout, StoreEvent, StoreGuest, StoreRsvpResponse, UpdateEvent)
├── Livewire/
│   └── Forms/LoginForm.php
├── Models/
│   ├── BillingWebhookEvent, Event, EventBilling, EventTable, Guest, Invitation, Organization, OrganizationUser
│   ├── Payment, Plan, RsvpResponse, SeatAssignment, User
├── Policies/
│   ├── EventPolicy, GuestPolicy, OrganizationPolicy, PaymentPolicy
├── Providers/
│   ├── AppServiceProvider.php
│   └── VoltServiceProvider.php
├── Services/
│   ├── BillingService.php
│   ├── OrganizationContext.php
│   ├── StubPaymentGateway.php
│   ├── SumitPaymentGateway.php
│   └── Sumit/EventBillingPayable.php
└── View/Components/
    ├── AppLayout.php
    └── GuestLayout.php
```
**Note:** `app/Settings/` folder is empty (SiteSettings deleted in PHASE 7).

### routes/
- `api.php` — Event SaaS API + RSVP + webhooks
- `auth.php` — Auth (login, register, password, verify)
- `console.php` — Artisan
- `web.php` — Event SaaS web (dashboard, organizations, events, event/{slug}, rsvp, checkout)
- `workflows.php` — Empty (no workflow definitions)

### resources/ (high-level)
- `css/app.css` — Tailwind
- `js/` — app.js, bootstrap.js, flowbite*, grapesjs, etc. (many legacy JS files remain)
- `lang/` — en, fr, he + vendor (backup, bladewind, filament, officeguy, zeus-dynamic-dashboard)
- `views/` — auth, checkout, dashboard, errors, events, layouts, livewire/pages/auth, rsvp; plus invoices, payments, components/emails, components/public, components/payment-gateways, flowbite, bladewind, etc.

### database/
- `migrations/` — See STEP 3 table.
- `seeders/` — Not fully enumerated here.
- `factories/` — Not fully enumerated here.

### config/
- See STEP 4.

---

## Classification (STEP 1)

| Area | A) Event SaaS domain | B) Infrastructure | C) Legacy / foreign |
|------|----------------------|-------------------|----------------------|
| **app/** | Contracts, Enums (event/org/rsvp/billing/payment), Http (Api Event/Guest/Invitation/Checkout/Payment/Webhook, Dashboard, Public), Livewire/Forms/LoginForm, Models (Event, Guest, Invitation, EventTable, SeatAssignment, EventBilling, Payment, Plan, Organization, User, BillingWebhookEvent), Policies, Services (Billing, OrganizationContext, Sumit, Stub), View/Components | Auth (VerifyEmail), Middleware (EnsureOrganizationSelected), Providers (App, Volt) | **None in app/** |
| **routes/** | web.php (dashboard, events, rsvp, checkout), api.php (events, guests, invitations, checkout, payments, rsvp) | auth.php, console.php | workflows.php (empty, no harm) |
| **resources/views** | auth, checkout (tokenize, status), dashboard, errors, events, layouts (app, guest, client), livewire/pages/auth, rsvp, components (dynamic-navbar, input-*, text-input, textarea, user-avatar-menu) | — | invoices/, payments/cardcom*, components/emails, components/public, components/payment-gateways (cardcom*), components/flowbite, components/bladewind, components/tailadmin, navigation-topbar-OLD-BACKUP, many layout partials (navbar*, client-sidebar, etc.) |
| **resources/js** | app.js, bootstrap.js (if used for Event UI) | — | flowbite-*, grapesjs*, payment-gateway-integration, checkout-validation, etc. (many legacy) |
| **resources/lang** | — | en, fr, he (if used) | vendor/filament, vendor/bladewind, vendor/zeus-dynamic-dashboard |

---

## STEP 2 — App Namespace Audit

**Scanned:** `app/` for domains unrelated to Organization, Event, Guest, Invitation, RSVP, Table, Seating, Billing, Payment (OfficeGuy only).

**Result:**

- **No** standalone Invoice system in `app/` (no `App\Services\Invoice`, no `App\Models\Invoice` in current tree).
- **No** Email branding service class in `app/` (only Blade views reference config).
- **No** CMS / Page builders in `app/`.
- **No** Marketplace / Orders models or controllers in `app/` (removed in PHASE 2).
- **No** Settings engines in `app/` (SiteSettings deleted; Settings folder empty).
- **No** Feature-flag system in `app/`.
- **No** Filament providers or panels in `app/`.
- **No** Cardcom or Stripe adapters in `app/` (only `PaymentGatewayInterface` + `SumitPaymentGateway` + `StubPaymentGateway`).
- **Single mention of "Stripe":** `app/Http/Controllers/Api/WebhookController.php` — header fallback `Stripe-Signature` in a generic signature check. **Classification:** Infrastructure (webhook verification pattern), not a Stripe integration. **Flag:** Optional rename to a generic header name in a later cleanup.

**Conclusion:** **app/** is clean. No foreign domains; only Event SaaS + Auth + Billing (OfficeGuy) + Livewire (LoginForm).

---

## STEP 3 — Database Migration Audit

| Migration | Table / purpose | Classification |
|-----------|------------------|----------------|
| `0001_01_01_000000_create_users_table.php` | users | **Auth** |
| `0001_01_01_000001_create_cache_table.php` | cache | **Infrastructure** |
| `0001_01_01_000002_create_jobs_table.php` | jobs | **Infrastructure** |
| `2022_12_14_083707_create_settings_table.php` | settings | **Orphaned (legacy)** |
| `2025_01_01_000008_create_webhook_events_table.php` | webhook_events | **Billing core** |
| `2025_01_01_000009_create_sumit_incoming_webhooks_table.php` | sumit_incoming_webhooks | **Billing core** |
| `2025_09_01_000000_create_clients_table.php` | clients | **Orphaned (legacy)** |
| `2025_11_17_154731_add_workflow_fields_to_users_table.php` | users (workflow fields) | **Orphaned (legacy)** |
| `2025_11_17_154731_create_businesses_table.php` | businesses | **Orphaned (legacy)** |
| `2025_11_17_154733_create_payment_methods_table.php` | payment_methods | **Orphaned (legacy)** |
| `2025_11_17_154734_create_providers_table.php` | providers | **Orphaned (legacy)** |
| `2025_11_17_154734_create_services_table.php` | services | **Orphaned (legacy)** |
| `2025_11_17_154735_create_appointments_table.php` | appointments | **Orphaned (legacy)** |
| `2025_11_17_154736_create_cart_items_table.php` | cart_items | **Orphaned (legacy)** |
| `2025_11_17_154736_create_orders_table.php` | orders | **Orphaned (legacy)** |
| `2025_11_17_154737_create_order_items_table.php` | order_items | **Orphaned (legacy)** |
| `2025_12_26_000001_add_transaction_linking_fields_to_officeguy_transactions.php` | officeguy_transactions | **Billing / OfficeGuy** |
| `2026_03_01_092523_create_personal_access_tokens_table.php` | personal_access_tokens | **Auth (Sanctum)** |
| `2026_03_01_100000_create_organizations_table.php` | organizations | **Event SaaS** |
| `2026_03_01_100001_create_organization_users_table.php` | organization_users | **Event SaaS** |
| `2026_03_01_100002_create_events_table.php` | events | **Event SaaS** |
| `2026_03_01_100003_create_guests_table.php` | guests | **Event SaaS** |
| `2026_03_01_100004_create_invitations_table.php` | invitations | **Event SaaS** |
| `2026_03_01_100005_create_rsvp_responses_table.php` | rsvp_responses | **Event SaaS** |
| `2026_03_01_100006_create_tables_table.php` | tables (event_tables) | **Event SaaS** |
| `2026_03_01_100007_create_seat_assignments_table.php` | seat_assignments | **Event SaaS** |
| `2026_03_01_100008_create_plans_table.php` | plans | **Event SaaS / Billing** |
| `2026_03_01_100009_create_events_billing_table.php` | events_billing | **Event SaaS / Billing** |
| `2026_03_01_100010_create_payments_table.php` | payments | **Event SaaS / Billing** |
| `2026_03_01_100011_create_billing_webhook_events_table.php` | billing_webhook_events | **Billing core** |
| `2026_03_01_120000_events_billing_event_id_restrict_on_delete.php` | FK | **Event SaaS** |
| `2026_03_01_130000_payments_organization_id_restrict_on_delete.php` | FK | **Event SaaS** |

**Orphaned migrations (no longer have app/ models or domain):**  
settings, clients, workflow_fields (users), businesses, payment_methods, providers, services, appointments, cart_items, orders, order_items.

**Not run yet;** marked for future removal or down-migrations in a dedicated cleanup.

---

## STEP 4 — Config Audit

| File | Required for | Classification |
|------|----------------|------------------|
| `app.php` | Laravel core | **Keep** |
| `auth.php` | Auth | **Keep** |
| `billing.php` | Billing / OfficeGuy (gateway, sumit) | **Keep** |
| `cache.php` | Cache | **Keep** |
| `database.php` | DB | **Keep** |
| `filesystems.php` | Storage | **Keep** |
| `livewire-workflows.php` | Livewire Workflows package | **Suspect** — empty workflows; remove when package removed (Composer phase) |
| `livewire.php` | Livewire | **Keep** |
| `logging.php` | Logging | **Keep** |
| `mail.php` | Mail | **Keep** |
| `officeguy.php` | OfficeGuy Sumit gateway | **Keep** |
| `queue.php` | Queue | **Keep** |
| `sanctum.php` | Sanctum | **Keep** |
| `services.php` | Postmark, Resend, SES, Slack (no Stripe/Cardcom) | **Keep** (Laravel default) |
| `session.php` | Session | **Keep** |
| `settings.php` | Spatie Laravel Settings | **Suspect** — no app/Settings classes; remove when package removed |

**No** config files found for: cardcom, stripe, invoice (standalone), marketing, cms, feature flags, or filament panels.

**officeguy.php** contains invoice-related keys (e.g. invoice_currency_code) used for OfficeGuy invoice generation — **Infrastructure (Billing)**.

---

## STEP 5 — Final Verification Before Composer

| Check | Status |
|-------|--------|
| No foreign domains in **app/** | **PASS** — app/ is Event SaaS + Auth + Billing (OfficeGuy) + Livewire (LoginForm) only. |
| Orphan migrations | **Present** — 10 orphaned migrations (settings, clients, businesses, payment_methods, providers, services, appointments, cart_items, orders, order_items, workflow_fields). Not run yet; to be removed or down in a future phase. |
| Unused configs | **2 suspect** — `config/settings.php`, `config/livewire-workflows.php`. Remove when corresponding packages are removed in Composer phase. |
| Legacy services in app/ | **None** — no Invoice, MenuService, Settings, Cardcom, Stripe, Filament in app/. |

**Resources/views and resources/js:**  
- **Legacy / foreign in views:** invoices/, payments/cardcom*, components/emails, components/public, components/payment-gateways (cardcom), flowbite, bladewind, tailadmin, navigation-topbar-OLD-BACKUP, layouts/partials (navbar*, client-*).  
- **Legacy in js:** Many files (flowbite-*, grapesjs*, payment-gateway-integration, etc.).  

These are **outside app/** but still in the repo. They do not affect **app/** or **config** domain integrity. If desired, a **PHASE 8.1 — Domain Hard Cut** can remove or archive legacy view/asset trees; the directive was to audit and report, not delete yet.

---

## Recommendation

- **app/, routes/, config/ (except 2 suspect files), and Event SaaS migrations:** Aligned with Event SaaS only + required infrastructure.
- **Orphan migrations:** Documented; handle in a dedicated migration cleanup (no run yet).
- **Suspect configs:** `settings.php`, `livewire-workflows.php` — remove when removing Spatie Settings and Livewire Workflows in Composer phases.
- **Resources (views/js/lang):** Contain legacy/marketing/UI kit material; optional **PHASE 8.1** for view/asset cleanup.
- **Proceed to Composer phases (9–14)** from a domain-integrity perspective; no blocking foreign domains in app/.

---

*End of PHASE 8 Domain Integrity Audit*
