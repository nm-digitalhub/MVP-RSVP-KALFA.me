# MODEL_ROLE_SPEC — Host-Only, Evidence-Based

**Scope:** `app/Models/` and related sources (migrations, controllers, services, policies, Livewire, seeders).  
**Rule:** Investigation only. No code changes. All claims backed by file:line references; unproven → UNKNOWN.

---

## A) Global overview

### Glossary (extracted from code)

| Term | Meaning in code | Citations |
|------|-----------------|-----------|
| **Event** | A tenant-scoped occasion (date, venue, status); lifecycle: draft → pending_payment → active → …; owns guests, invitations, tables, seat assignments, and one event_billing. | `app/Models/Event.php` (fillable, relations, status cast); `app/Enums/EventStatus.php`; `app/Http/Controllers/Api/EventController.php:34` (create); `app/Services/BillingService.php:34,88` (status transitions). |
| **Organization** | Tenant boundary; has many events, event_billings, payments; has many users via pivot with role; has billing_email; can be suspended. | `app/Models/Organization.php`; `app/Http/Controllers/Api/EventController.php:22` (scoped by organization_id); `app/Livewire/Organizations/Create.php:24` (create). |
| **User** | Authenticatable; belongs to many organizations via `organization_users` with role; has current_organization_id; may be system_admin or disabled. | `app/Models/User.php`; `app/Http/Controllers/Auth/RegisterController.php:27` (create); `app/Http/Controllers/Auth/LoginController.php:38` (last_login_at update). |
| **Guest** | Invitee per event (name, email, phone, group_name); belongs to one event; may have one invitation and one seat_assignment. | `app/Models/Guest.php`; `app/Http/Controllers/Api/GuestController.php:29` (create via event); `database/migrations/2026_03_01_100003_create_guests_table.php`. |
| **Invitation** | Sendable link per event (token, slug, status, expires_at); links event to optional guest; has many rsvp_responses. | `app/Models/Invitation.php`; `app/Http/Controllers/Api/InvitationController.php:34` (create); `app/Http/Controllers/Api/PublicRsvpController.php:22,44` (find by slug). |
| **Billing** | One-time charge record for an event: amount, plan, status (pending/paid); created when checkout is initiated; has many payments (morph). | `app/Models/EventBilling.php` (table `events_billing`); `app/Services/BillingService.php:36,90` (create); `app/Services/SumitPaymentGateway.php:29,88` (load for charge). |
| **Payment** | Single payment attempt (amount, status, gateway, gateway_transaction_id); morphs to payable (EventBilling in this app). | `app/Models/Payment.php`; `app/Services/BillingService.php:45,99` (create); `app/Http/Controllers/Api/WebhookController.php:40` (idempotency by gateway_transaction_id). |
| **Plan** | Pricing tier (name, slug, type, price_cents); referenced by EventBilling; seeded for per_event checkout. | `app/Models/Plan.php`; `database/seeders/PlanSeeder.php`; `app/Http/Controllers/Api/CheckoutController.php:31`. |
| **Reservation / Booking** | Not a separate model. “Reservation” in domain is represented by Event (and its status); payment is tied to Event via EventBilling. | N/A — inferred from Event + EventBilling usage. |

### Dependency map (roots vs supporting)

- **Aggregate roots (created first or as entry points):**
  - **User** — created at registration; no parent in app.
  - **Organization** — created by authenticated user; first member attached as Owner via pivot.
  - **Event** — created under Organization; lifecycle and billing hang off it.
  - **Plan** — seeded; referenced by EventBilling (catalog entity).

- **Child / supporting (always under a root):**
  - **OrganizationUser** — pivot; created via `User::organizations()->attach(..., ['role'=>...])` (e.g. `app/Livewire/Organizations/Create.php:29-31`).
  - **EventBilling** — created when payment is initiated for an Event; belongs to Organization, Event, Plan.
  - **Payment** — created on EventBilling; belongs to Organization; polymorphic payable → EventBilling.
  - **Guest** — created under Event (`event->guests()->create()`).
  - **Invitation** — created under Event (`event->invitations()->create()`); may reference Guest.
  - **RsvpResponse** — created/updated under Invitation (public RSVP flow); references Invitation and Guest.
  - **EventTable** — created under Event (`event->eventTables()->create()`).
  - **SeatAssignment** — created/updated under Event; links Guest + EventTable.
  - **BillingWebhookEvent** — created in webhook handler; audit only.
  - **SystemAuditLog** — created by SystemAuditLogger; actor → User, target → morph.

---

## B) Per-model spec

### User

- **Location:** `app/Models/User.php`
- **Table / PK:** `users`, `id` (bigint), timestamps yes (migration `0001_01_01_000000_create_users_table.php`).
- **Local meaning (role):** The authenticated identity and membership holder; owns organization membership (via pivot) and current_organization_id; may be system admin or disabled. Evidence: `User.php` extends Authenticatable; fillable includes `current_organization_id`, `is_system_admin`, `is_disabled`; `currentOrganization()` resolves active tenant from DB.
- **Lifecycle owners:** RegisterController (create); LoginController (last_login_at update); OrganizationContext, OrganizationSwitchController, ImpersonationController/Exit, Livewire Create (current_organization_id update); Livewire System/Users (is_system_admin, is_disabled, password); Livewire Profile (email, password).
- **Identifiers:**
  - **id:** Local primary key (evidence: default Eloquent).
  - **Foreign keys:** None on users table. current_organization_id references organizations.id (logical; constraint in later migration if any — not in base users migration; add_current_organization_id migration adds column).
  - **External IDs:** None on User model.
- **Required invariants (proven):** email unique (migration); current_organization_id may be null; when set, membership enforced in `currentOrganization()` (User.php:95-98).
- **Relations:** ownedOrganizations (BelongsToMany, pivot role Owner); organizations (BelongsToMany, pivot role); currentOrganization() (method, not relation).
- **Hot paths:** Login (LoginController); organization switch (OrganizationSwitchController, OrganizationContext); system admin actions (Livewire System/Users/Show, Index); impersonation (SystemImpersonationController, Exit); create organization (Livewire Organizations/Create — attach as Owner).
- **Evidence index:**
  - `app/Models/User.php:21-29` (fillable), `84-99` (currentOrganization).
  - `app/Http/Controllers/Auth/RegisterController.php:27` (User::create).
  - `app/Http/Controllers/Auth/LoginController.php:38` (last_login_at update).
  - `database/migrations/0001_01_01_000000_create_users_table.php` (table, email unique).
  - `database/migrations/2026_03_03_100000_add_current_organization_id_to_users_table.php`, `2026_03_02_224239_add_is_system_admin_to_users_table.php`, `2026_03_04_120000_add_last_login_at_and_is_disabled_to_users_table.php`.

---

### Organization

- **Location:** `app/Models/Organization.php`
- **Table / PK:** `organizations`, `id` (bigint), timestamps yes (migration `2026_03_01_100000_create_organizations_table.php`).
- **Local meaning (role):** Tenant boundary; owns events, event_billings, payments; has members (users) with roles; billing_email used for payment customer data; can be suspended. Evidence: relations events(), eventsBilling(), payments(), users(); owner() from pivot role; fillable includes is_suspended (from add_is_suspended migration).
- **Lifecycle owners:** Livewire Organizations/Create (create); Api OrganizationController (update); Livewire System/Organizations/Show (suspend, activate, force delete, transfer ownership).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** None on organizations table (it is the referenced side).
  - **External IDs:** None.
- **Required invariants (proven):** slug unique (migration); organization_id required on events, events_billing, payments (FK constraints).
- **Relations:** users (BelongsToMany, organization_users); events; eventsBilling; payments; owner() (User with Owner role).
- **Hot paths:** Create org + attach owner (Livewire Organizations/Create); dashboard/API scoping by organization (EventController, policies); billing (BillingService, SumitPaymentGateway use organization_id); system admin org management (Show: suspend, activate, delete, transfer).
- **Evidence index:**
  - `app/Models/Organization.php:14-20` (fillable), `37-48` (relations).
  - `app/Livewire/Organizations/Create.php:24-31` (Organization::create, attach with role).
  - `app/Http/Controllers/Api/OrganizationController.php:31` (update).
  - `app/Services/BillingService.php:37-38,54` (organization_id on EventBilling, Payment).
  - `database/migrations/2026_03_01_100000_create_organizations_table.php`; `2026_03_04_110000_add_is_suspended_to_organizations_table.php`.

---

### OrganizationUser

- **Location:** `app/Models/OrganizationUser.php`
- **Table / PK:** `organization_users`, `id` (bigint), timestamps yes (migration `2026_03_01_100001_create_organization_users_table.php`).
- **Local meaning (role):** Pivot between User and Organization with role (owner, admin, editor, viewer). Evidence: table name; fillable organization_id, user_id, role; cast role to OrganizationUserRole; relations to Organization and User.
- **Lifecycle owners:** Created/updated via User↔Organization attach/detach/updateExistingPivot (e.g. Livewire Organizations/Create attach; Livewire System/Organizations/Show transfer ownership via updateExistingPivot).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** organization_id (organizations), user_id (users); unique(organization_id, user_id).
  - **External IDs:** None.
- **Required invariants (proven):** Unique (organization_id, user_id) (migration).
- **Relations:** organization (BelongsTo); user (BelongsTo).
- **Hot paths:** Create organization (attach as Owner); OrganizationPolicy isOwnerOrAdmin (pivot role); transfer ownership (Show: updateExistingPivot role).
- **Evidence index:**
  - `app/Models/OrganizationUser.php` (full file).
  - `app/Livewire/Organizations/Create.php:29-31` (attach with role).
  - `app/Livewire/System/Organizations/Show.php:82-84` (updateExistingPivot for transfer).
  - `app/Policies/OrganizationPolicy.php:39-41` (role from pivot).
  - `database/migrations/2026_03_01_100001_create_organization_users_table.php`.

---

### Event

- **Location:** `app/Models/Event.php`
- **Table / PK:** `events`, `id` (bigint), timestamps yes, soft deletes (migration `2026_03_01_100002_create_events_table.php`).
- **Local meaning (role):** Tenant-scoped event (name, slug, event_date, venue_name, status); the main bookable/reservable entity; status drives draft → pending_payment → active. Evidence: EventStatus cast; BillingService transitions status; policies scoped by organization_id.
- **Lifecycle owners:** Api EventController (create, update); BillingService (status → PendingPayment, then event->update status Active on payment success); system/admin may delete (soft) — UNKNOWN if explicit delete endpoint.
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** organization_id (organizations), constrained.
  - **External IDs:** None on Event.
- **Required invariants (proven):** organization_id required; unique(organization_id, slug); status values from EventStatus enum.
- **Relations:** organization; guests; invitations; eventTables; seatAssignments; eventBilling (HasOne EventBilling).
- **Hot paths:** Event CRUD (EventController); checkout initiation (CheckoutController → BillingService); public event page (PublicEventController by slug); public RSVP (PublicRsvpController requires event active); dashboard event list; seat assignments (SeatAssignmentController).
- **Evidence index:**
  - `app/Models/Event.php:18-26` (fillable, status cast), `37-64` (relations).
  - `app/Http/Controllers/Api/EventController.php:22,32,34,55` (index, create, update).
  - `app/Services/BillingService.php:34,88` (status PendingPayment), `158` (status Active on success).
  - `app/Enums/EventStatus.php`.
  - `database/migrations/2026_03_01_100002_create_events_table.php`.

---

### EventBilling

- **Location:** `app/Models/EventBilling.php`
- **Table / PK:** `events_billing`, `id` (bigint), timestamps yes (migration `2026_03_01_100009_create_events_billing_table.php`).
- **Local meaning (role):** One billing record per event payment; holds amount, plan, currency, status (pending/paid); created when checkout is initiated; payments morph to it. Evidence: table events_billing; fillable organization_id, event_id, plan_id, amount_cents, currency, status, paid_at; payments() morphMany Payment.
- **Lifecycle owners:** BillingService only (create on initiateEventPayment / initiateEventPaymentWithToken; update status/paid_at on markPaymentSucceeded); read by SumitPaymentGateway (load by id for charge).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** organization_id, event_id, plan_id (all constrained).
  - **External IDs:** None on EventBilling (payment gateway IDs live on Payment).
- **Required invariants (proven):** organization_id, event_id required; event_id unique per event (HasOne from Event); status from EventBillingStatus.
- **Relations:** organization; event; plan; payments (MorphMany).
- **Hot paths:** Checkout flow (BillingService create; SumitPaymentGateway load and wrap in EventBillingPayable); webhook success (BillingService markPaymentSucceeded updates EventBilling and Event status).
- **Evidence index:**
  - `app/Models/EventBilling.php:14` (table), `16-24` (fillable), `50-52` (payments morph).
  - `app/Services/BillingService.php:36-42,90-96` (create), `154-157` (update status/paid_at).
  - `app/Services/SumitPaymentGateway.php:29-31,88-90` (EventBilling::with(...)->findOrFail).
  - `database/migrations/2026_03_01_100009_create_events_billing_table.php`; `2026_03_01_120000_events_billing_event_id_restrict_on_delete.php`.

---

### Payment

- **Location:** `app/Models/Payment.php`
- **Table / PK:** `payments`, `id` (bigint), timestamps yes (migration `2026_03_01_100010_create_payments_table.php`).
- **Local meaning (role):** A single payment attempt (amount, currency, status, gateway, gateway_transaction_id); polymorphic payable → EventBilling; used for idempotency and webhook handling. Evidence: fillable payable_type, payable_id, gateway, gateway_transaction_id; payable() MorphTo; WebhookController checks gateway_transaction_id + status.
- **Lifecycle owners:** BillingService (create on initiate*; update gateway_transaction_id, gateway_response, status); SumitPaymentGateway handleWebhook (find by gateway_transaction_id); BillingService markPaymentSucceeded/Failed (status update).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** organization_id (organizations); payable_type, payable_id (polymorphic).
  - **External IDs:** gateway_transaction_id — provider transaction ID (unique in migration); used for idempotency (WebhookController, SumitPaymentGateway).
- **Required invariants (proven):** organization_id required; unique(gateway_transaction_id); payable_type/payable_id point to EventBilling in this app.
- **Relations:** organization; payable (MorphTo → EventBilling).
- **Hot paths:** Checkout (BillingService create + update transaction_id); webhook (WebhookController, SumitPaymentGateway find by gateway_transaction_id; BillingService markPaymentSucceeded/Failed).
- **Evidence index:**
  - `app/Models/Payment.php:14-24` (fillable), `40` (payable morphTo).
  - `app/Services/BillingService.php:45-51,63-66,99-101,119-121,130,150,169` (create/update).
  - `app/Http/Controllers/Api/WebhookController.php:40` (idempotency by gateway_transaction_id).
  - `app/Services/SumitPaymentGateway.php:141` (Payment::where('gateway_transaction_id', ...)).
  - `database/migrations/2026_03_01_100010_create_payments_table.php`.

---

### Plan

- **Location:** `app/Models/Plan.php`
- **Table / PK:** `plans`, `id` (bigint), timestamps yes (migration `2026_03_01_100008_create_plans_table.php`).
- **Local meaning (role):** Pricing tier (name, slug, type, limits, price_cents, billing_interval); referenced by EventBilling; seeded for per_event. Evidence: eventsBilling() HasMany; CheckoutController/CheckoutTokenizeController find Plan; PlanSeeder firstOrCreate.
- **Lifecycle owners:** PlanSeeder (firstOrCreate); no app controller create/update in scope — UNKNOWN if admin UI exists.
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** None (referenced by events_billing.plan_id).
  - **External IDs:** None.
- **Required invariants (proven):** slug unique (migration); type e.g. per_event used in checkout (CheckoutTokenizeController).
- **Relations:** eventsBilling (HasMany).
- **Hot paths:** Checkout (CheckoutController plan_id validation; CheckoutTokenizeController Plan::where('type','per_event')); BillingService (plan_id, price_cents on EventBilling).
- **Evidence index:**
  - `app/Models/Plan.php` (fillable, eventsBilling).
  - `app/Http/Controllers/Api/CheckoutController.php:31` (Plan::findOrFail).
  - `app/Http/Controllers/CheckoutTokenizeController.php:23` (Plan::where('type','per_event')).
  - `database/seeders/PlanSeeder.php`; `database/migrations/2026_03_01_100008_create_plans_table.php`.

---

### Guest

- **Location:** `app/Models/Guest.php`
- **Table / PK:** `guests`, `id` (bigint), timestamps yes, soft deletes (migration `2026_03_01_100003_create_guests_table.php`).
- **Local meaning (role):** Invitee for one event (name, email, phone, group_name, notes, sort_order); may have one invitation and one seat assignment. Evidence: belongsTo Event; hasOne Invitation, hasOne SeatAssignment; GuestController CRUD under event.
- **Lifecycle owners:** Api GuestController (create via event->guests()->create, update, destroy); GuestImportController (Guest::create in loop).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** event_id (events), constrained.
  - **External IDs:** None.
- **Required invariants (proven):** event_id required; guest scoped to event in policies (GuestPolicy).
- **Relations:** event; invitation (HasOne); rsvpResponses; seatAssignment (HasOne).
- **Hot paths:** Guest CRUD (GuestController); invitation create (InvitationController may set guest_id); RSVP (PublicRsvpController uses invitation.guest); seat assignment (SeatAssignment links guest_id); guest import (GuestImportController).
- **Evidence index:**
  - `app/Models/Guest.php:17-25` (fillable), `34-51` (relations).
  - `app/Http/Controllers/Api/GuestController.php:29,54,61` (create, update, destroy).
  - `app/Http/Controllers/Api/GuestImportController.php:71` (Guest::create).
  - `database/migrations/2026_03_01_100003_create_guests_table.php`.

---

### Invitation

- **Location:** `app/Models/Invitation.php`
- **Table / PK:** `invitations`, `id` (bigint), timestamps yes (migration `2026_03_01_100004_create_invitations_table.php`). token, slug unique.
- **Local meaning (role):** Sendable link for an event (token, slug, status, expires_at, responded_at); optionally tied to a guest; receives RSVP responses. Evidence: event_id, guest_id nullable; rsvpResponses HasMany; PublicRsvpController find by slug; status InvitationStatus.
- **Lifecycle owners:** Api InvitationController (create via event->invitations()->create; update status Sent); PublicRsvpController (update status Responded, responded_at).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** event_id (events), guest_id (guests, nullable).
  - **External IDs:** token, slug — unique link identifiers (not third-party; app-generated).
- **Required invariants (proven):** event_id required; token unique, slug unique (migration).
- **Relations:** event; guest; rsvpResponses (HasMany).
- **Hot paths:** Create/send invitation (InvitationController); public RSVP by slug (PublicRsvpController, PublicRsvpViewController); RSVP submit (RsvpResponse updateOrCreate + invitation update).
- **Evidence index:**
  - `app/Models/Invitation.php:14-22` (fillable), `33-45` (relations).
  - `app/Http/Controllers/Api/InvitationController.php:34,55` (create, update status).
  - `app/Http/Controllers/Api/PublicRsvpController.php:22,44,63` (find by slug, invitation update).
  - `database/migrations/2026_03_01_100004_create_invitations_table.php`.

---

### RsvpResponse

- **Location:** `app/Models/RsvpResponse.php`
- **Table / PK:** `rsvp_responses`, `id` (bigint), timestamps yes (migration `2026_03_01_100005_create_rsvp_responses_table.php`).
- **Local meaning (role):** A guest’s response to an invitation (response: attending/declining/maybe, attendees_count, message, ip, user_agent). Evidence: invitation_id, guest_id; response cast to RsvpResponseType; created/updated in public RSVP flow.
- **Lifecycle owners:** PublicRsvpController, PublicRsvpViewController (RsvpResponse::updateOrCreate keyed by invitation_id + guest_id).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** invitation_id (invitations), guest_id (guests, nullable).
  - **External IDs:** None.
- **Required invariants (proven):** invitation_id required; response enum; one logical response per invitation (enforced by updateOrCreate keys).
- **Relations:** invitation; guest.
- **Hot paths:** Public RSVP submit (PublicRsvpController storeResponse; PublicRsvpViewController equivalent).
- **Evidence index:**
  - `app/Models/RsvpResponse.php:14-22` (fillable), `33-41` (relations).
  - `app/Http/Controllers/Api/PublicRsvpController.php:53-62` (updateOrCreate + invitation update).
  - `app/Http/Controllers/PublicRsvpViewController.php:43` (updateOrCreate).
  - `database/migrations/2026_03_01_100005_create_rsvp_responses_table.php`.

---

### EventTable

- **Location:** `app/Models/EventTable.php`
- **Table / PK:** `event_tables`, `id` (bigint), timestamps yes, soft deletes (migration `2026_03_01_100006_create_tables_table.php` creates `event_tables`).
- **Local meaning (role):** A table or area within an event (name, capacity, sort_order); used for seat assignments. Evidence: event_id; seatAssignments HasMany; EventTableController create/update under event.
- **Lifecycle owners:** Api EventTableController (event->eventTables()->create; update).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** event_id (events), constrained.
  - **External IDs:** None.
- **Required invariants (proven):** event_id required.
- **Relations:** event; seatAssignments (HasMany).
- **Hot paths:** Event tables CRUD (EventTableController); seat assignments (SeatAssignment references event_table_id).
- **Evidence index:**
  - `app/Models/EventTable.php:16` (table), `18-23` (fillable), `33-40` (relations).
  - `app/Http/Controllers/Api/EventTableController.php:34,56` (create, update).
  - `database/migrations/2026_03_01_100006_create_tables_table.php` (creates event_tables).

---

### SeatAssignment

- **Location:** `app/Models/SeatAssignment.php`
- **Table / PK:** `seat_assignments`, `id` (bigint), timestamps yes (migration `2026_03_01_100007_create_seat_assignments_table.php`). Unique (event_id, guest_id).
- **Local meaning (role):** Assigns a guest to an event table (and optional seat_number) for one event. Evidence: event_id, guest_id, event_table_id, seat_number; unique(event_id, guest_id) in migration; SeatAssignmentController updateOrCreate by event_id+guest_id.
- **Lifecycle owners:** Api SeatAssignmentController (bulk updateOrCreate keyed by event_id, guest_id).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** event_id (events), guest_id (guests), event_table_id (event_tables).
  - **External IDs:** None.
- **Required invariants (proven):** event_id, guest_id, event_table_id required; unique(event_id, guest_id).
- **Relations:** event; guest; eventTable (BelongsTo event_table_id).
- **Hot paths:** Seat assignment bulk update (SeatAssignmentController).
- **Evidence index:**
  - `app/Models/SeatAssignment.php:12-17` (fillable), `19-31` (relations).
  - `app/Http/Controllers/Api/SeatAssignmentController.php:39-50` (updateOrCreate).
  - `database/migrations/2026_03_01_100007_create_seat_assignments_table.php`.

---

### BillingWebhookEvent

- **Location:** `app/Models/BillingWebhookEvent.php`
- **Table / PK:** `billing_webhook_events`, `id` (bigint), timestamps yes (migration `2026_03_01_100011_create_billing_webhook_events_table.php`).
- **Local meaning (role):** Audit log of incoming billing webhook payloads (source, event_type, payload, processed_at). Evidence: fillable source, event_type, payload, processed_at; WebhookController create then update processed_at.
- **Lifecycle owners:** Api WebhookController only (create on receive; update processed_at after handling).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** None.
  - **External IDs:** source/event_type/payload come from external gateway; not stored as separate “provider ID” column.
- **Required invariants (proven):** None beyond table structure.
- **Relations:** None.
- **Hot paths:** Webhook handler (WebhookController).
- **Evidence index:**
  - `app/Models/BillingWebhookEvent.php:11` (table), `13-19` (fillable).
  - `app/Http/Controllers/Api/WebhookController.php:44,57` (create, update processed_at).
  - `database/migrations/2026_03_01_100011_create_billing_webhook_events_table.php`.

---

### SystemAuditLog

- **Location:** `app/Models/SystemAuditLog.php`
- **Table / PK:** `system_audit_logs`, `id` (bigint), timestamps yes (migration `2026_03_04_100000_create_system_audit_logs_table.php`).
- **Local meaning (role):** Audit record of who did what to what (actor_id → User; target morph; action; metadata; ip_address; user_agent). Evidence: actor BelongsTo User; target MorphTo; SystemAuditLogger::log creates entries for system admin and org actions.
- **Lifecycle owners:** SystemAuditLogger::log only (called from Livewire System/Organizations/Show, System/Users/Show, System/Users/Index; ImpersonationController, ImpersonationExitController; ImpersonationExpiry middleware).
- **Identifiers:**
  - **id:** Local primary key.
  - **Foreign keys:** actor_id (users, nullable).
  - **External IDs:** target_type/target_id are polymorphic (internal model references).
- **Required invariants (proven):** action required; actor_id nullable (migration).
- **Relations:** actor (BelongsTo User); target (MorphTo).
- **Hot paths:** System admin actions (transfer ownership, suspend/activate org, force delete, promote/demote admin, disable user, password reset); impersonation start/exit/expiry.
- **Evidence index:**
  - `app/Models/SystemAuditLog.php:13-21` (fillable), `30-37` (relations).
  - `app/Services/SystemAuditLogger.php:29` (SystemAuditLog::create).
  - `app/Livewire/System/Organizations/Show.php:85,97,103,114,121,128`; `app/Livewire/System/Users/Show.php:80,92,99,107,113`; `app/Livewire/System/Users/Index.php:40`; impersonation controllers and middleware.
  - `database/migrations/2026_03_04_100000_create_system_audit_logs_table.php`.

---

## C) Cross-model matrices

### Ownership graph (parent → children)

```
User
├── (membership) → OrganizationUser ← Organization
│
Organization
├── Event
│   ├── Guest
│   │   ├── Invitation (optional guest_id)
│   │   │   └── RsvpResponse
│   │   └── SeatAssignment
│   ├── Invitation
│   ├── EventTable
│   │   └── SeatAssignment
│   ├── SeatAssignment
│   └── EventBilling
│       └── Payment (morph)
├── EventBilling
└── Payment

Plan (reference only)
└── EventBilling

SystemAuditLog
├── actor → User
└── target → (morph: Organization, User, …)

BillingWebhookEvent (standalone audit)
```

Evidence: relations in each model (BelongsTo/HasMany/HasOne/MorphTo); create flows (EventController→Event; GuestController→Guest; InvitationController→Invitation; BillingService→EventBilling, Payment; etc.) as cited in B.

### External integration touchpoints (proven)

| Model    | Field / flow                    | Meaning (evidence) |
|----------|----------------------------------|--------------------|
| Payment  | gateway_transaction_id          | Provider transaction ID; unique; used for idempotency (WebhookController:40, SumitPaymentGateway:141). |
| Payment  | gateway, gateway_response       | Gateway name and raw response (fillable; BillingService update). |
| BillingWebhookEvent | source, event_type, payload | Incoming webhook origin and payload (WebhookController:44). |

No other model fields in `app/Models` are proven to store third-party provider IDs. Organization.billing_email is used as customer email in payment flow (EventBillingPayable) but is not an “integration ID” column.

---

## Verification checklist

- [x] **Every model in app/Models appears exactly once in the spec.**  
  Listed: User, Organization, OrganizationUser, Event, EventBilling, Payment, Plan, Guest, Invitation, RsvpResponse, EventTable, SeatAssignment, BillingWebhookEvent, SystemAuditLog (14/14).

- [x] **Every claim has at least one evidence reference (file:line or file); otherwise marked UNKNOWN.**  
  All per-model and matrix claims cite file paths and/or line ranges; “UNKNOWN” used only where noted (e.g. Plan admin UI, explicit Event delete endpoint).

- [x] **No code changes were made.**  
  Investigation only; no files modified.
