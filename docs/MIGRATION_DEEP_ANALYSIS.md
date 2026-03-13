# Migration Deep Analysis — Step 1 Supplement
## Kalfa.me: Feature Inventory · State Flow Maps · Frontend Dependency Graph · Echo/Reverb Audit

> Generated: 2026-03-13  
> Companion to: `docs/MIGRATION_ANALYSIS.md`  
> Scope: No code was changed. Analysis only.

---

## Part A — Feature Inventory (Route → Controller/Livewire → Model → Integration)

> Purpose: proof-of-no-regression baseline. Every user-facing capability is mapped to its implementation chain.

---

### A.1 Authentication

| Route | Method | Controller | Models Touched | Integrations | Notes |
|---|---|---|---|---|---|
| `GET /login` | web | `Auth\LoginController@create` | — | — | Renders `auth.login` |
| `POST /login` | web | `Auth\LoginController@store` | `User` | WebAuthn (credential count check) | Updates `last_login_at`; flashes `passkey_upgrade` if 0 credentials |
| `GET /register` | web | `Auth\RegisterController@create` | — | — | |
| `POST /register` | web | `Auth\RegisterController@store` | `User` | — | bcrypt password; auto-login |
| `POST /logout` | web+auth | `Auth\LogoutController` | — | — | Session invalidate + token regenerate |
| `GET /forgot-password` | guest | `Auth\PasswordController@create` | — | — | |
| `POST /forgot-password` | guest | `Auth\PasswordController@sendResetLink` | `User` | Mail | |
| `GET /reset-password/{token}` | guest | `Auth\PasswordController@edit` | — | — | |
| `POST /reset-password` | guest | `Auth\PasswordController@update` | `User` | — | |
| `GET /verify-email` | auth | `Auth\VerificationController@notice` | — | — | |
| `POST /verify-email` | auth | `Auth\VerificationController@send` | `User` | Mail | |
| `GET /verify-email/{id}/{hash}` | signed | `Auth\VerifyEmailController` | `User` | — | |
| `GET /confirm-password` | auth | `Auth\ConfirmPasswordController@create` | — | — | |
| `POST /confirm-password` | auth | `Auth\ConfirmPasswordController@store` | — | — | Sets `session.passwordConfirmed` |

#### A.1.1 WebAuthn / Passkey

| Route | Method | Middleware | Controller | Models | Integrations |
|---|---|---|---|---|---|
| `POST /webauthn/login/options` | web+throttle:webauthn | `WebAuthnLoginController@options` | `User`, `webauthn_credentials` | laragear/webauthn | Returns JSON challenge |
| `POST /webauthn/login` | web+throttle:webauthn | `WebAuthnLoginController@login` | `User`, `webauthn_credentials` | laragear/webauthn | Verifies signature; logs audit |
| `POST /webauthn/register/options` | web+auth+throttle:webauthn | `WebAuthnRegisterController@options` | `webauthn_credentials` | laragear/webauthn | Soft-limits at 10 credentials |
| `POST /webauthn/register` | web+auth+throttle:webauthn | `WebAuthnRegisterController@register` | `webauthn_credentials` | laragear/webauthn | Saves credential; logs audit |

---

### A.2 Organization Management

| Route | Method | Controller/Livewire | Models | Integrations |
|---|---|---|---|---|
| `GET /organizations` | auth | `Livewire\Organizations\Index` | `Organization`, `OrganizationUser` | — |
| `GET /organizations/create` | auth | `Livewire\Organizations\Create` | — | — |
| `POST /organizations/switch/{org}` | auth | `OrganizationSwitchController` | `User`, `Organization` | `OrganizationContext::set()` |
| `GET /organization/settings` | auth+org | `Dashboard\OrganizationSettingsController@edit` | `Organization` | — |
| `PUT /organization/settings` | auth+org | `Dashboard\OrganizationSettingsController@update` | `Organization` | Spatie Media Library (logo) |

---

### A.3 Dashboard — Events

| Route | Method | Controller/Livewire | Models | Policy | Integrations |
|---|---|---|---|---|---|
| `GET /dashboard` | auth+org | `Dashboard` (Livewire) | `Event`, `Organization` | — | `OrganizationContext` |
| `GET /dashboard/events` | auth+org | `Dashboard\DashboardController@index` | `Event` | `EventPolicy@viewAny` | — |
| `GET /dashboard/events/create` | auth+org | `Dashboard\EventController@create` | — | `EventPolicy@create` | — |
| `POST /dashboard/events` | auth+org | `Dashboard\EventController@store` | `Event` | `EventPolicy@create` | — |
| `GET /dashboard/events/{event}` | auth+org | `Dashboard\EventController@show` | `Event` | `EventPolicy@view` | — |
| `GET /dashboard/events/{event}/edit` | auth+org | `Dashboard\EventController@edit` | `Event` | `EventPolicy@update` | — |
| `PUT /dashboard/events/{event}` | auth+org | `Dashboard\EventController@update` | `Event` | `EventPolicy@update` | Spatie Media Library |
| `DELETE /dashboard/events/{event}` | auth+org | `Dashboard\EventController@destroy` | `Event` | `EventPolicy@delete` | — |

---

### A.4 Dashboard — Event Sub-features

| Route | Livewire Component | Models | Policy | Integrations | Special Dependencies |
|---|---|---|---|---|---|
| `GET /dashboard/events/{event}/guests` | `Dashboard\EventGuests` | `Guest`, `Invitation` | `EventPolicy@update` | Echo (RsvpReceived) | `WithFileUploads`; `#[On('echo-private:event.{id},RsvpReceived')]` |
| `GET /dashboard/events/{event}/invitations` | `Dashboard\EventInvitations` | `Invitation`, `Guest` | `EventPolicy@update` | Echo (RsvpReceived) | `#[On('echo-private:event.{id},RsvpReceived')]` |
| `GET /dashboard/events/{event}/tables` | `Dashboard\EventTables` | `EventTable` | `EventPolicy@update` | — | `wire:sort` → `handleSort()` (Livewire 4 sort directive) |
| `GET /dashboard/events/{event}/seat-assignments` | `Dashboard\EventSeatAssignments` | `SeatAssignment`, `Guest`, `EventTable` | `EventPolicy@update` | — | — |

---

### A.5 Billing (Tenant)

| Route | Livewire Component | Models | Policy | Integrations |
|---|---|---|---|---|
| `GET /billing` | `Billing\AccountOverview` | `Account`, `AccountEntitlement` | `OrganizationPolicy@update` | `BillingService`, SUMIT |
| `GET /billing/entitlements` | `Billing\EntitlementsIndex` | `AccountEntitlement`, `ProductEntitlement` | `OrganizationPolicy@view` | `FeatureResolver` |
| `GET /billing/intents` | `Billing\BillingIntentsIndex` | `BillingIntent` | `OrganizationPolicy@view` | — |
| `GET /billing/usage` | `Billing\UsageIndex` | `AccountFeatureUsage`, `UsageRecord` | `OrganizationPolicy@view` | `UsageMeter` |

---

### A.6 Checkout / Payment

| Route | Controller | Models | Policy | Integrations | Notes |
|---|---|---|---|---|---|
| `GET /checkout/{org}/{event}` | `CheckoutTokenizeController` | `Event`, `Plan`, `Organization` | `EventPolicy@initiatePayment` | Sanctum (creates token), PaymentsJS (external embed), officeguy config | PCI page — renders tokenization form |
| `POST /api/.../checkout` | `Api\CheckoutController@initiate` | `Event`, `Plan`, `EventBilling`, `Payment` | `EventPolicy@initiatePayment` | `BillingService`, SUMIT gateway | Token flow OR redirect flow |
| `GET /checkout/status/{payment}` | `CheckoutStatusController@show` | `Payment` | `PaymentPolicy@view` | — | Polling page |
| `POST /api/webhooks/{gateway}` | `Api\WebhookController@handle` | `Payment`, `EventBilling`, `Event` | none (HMAC) | SUMIT/Bit gateway | Idempotent; transitions Event to Active on success |

---

### A.7 Public RSVP

| Route | Controller/Livewire | Models | Auth | Integrations | Side Effects |
|---|---|---|---|---|---|
| `GET /rsvp/{slug}` | `PublicRsvpViewController@show` | `Invitation`, `Event`, `Guest` | none | `EventLinks` (calendar links) | 404 if event not Active |
| `POST /rsvp/{slug}/responses` | `PublicRsvpViewController@store` | `RsvpResponse`, `Invitation` | none | — | DB transaction; updates `invitation.status` → Responded |
| `GET /api/rsvp/{slug}` | `Api\PublicRsvpController@showBySlug` | `Invitation`, `Event`, `Guest` | none | — | JSON; 404 if not Active |
| `POST /api/rsvp/{slug}/responses` | `Api\PublicRsvpController@storeResponse` | `RsvpResponse`, `Invitation` | none | — | Idempotent `updateOrCreate` |
| `GET /event/{slug}` | `PublicEventController@show` | `Event` | none | `EventLinks` | Public event info page |

---

### A.8 System Admin

| Route | Controller/Livewire | Models | Middleware | Integrations |
|---|---|---|---|---|
| `GET /system/dashboard` | `System\Dashboard` (Livewire) | `Organization`, `User`, `Event`, `Guest` | `system.admin` | `SystemBillingService` (MRR/churn stubs) |
| System users | `System\Users\Index`, `System\Users\Show` | `User`, `Organization` | `system.admin` | `SystemAuditLogger` |
| System orgs | `System\Organizations\Index`, `System\Organizations\Show` | `Organization`, `OrganizationUser` | `system.admin` | `SystemAuditLogger`; session flash |
| System accounts | `System\Accounts\Index`, `System\Accounts\Show`, `CreateAccountWizard` | `Account`, `AccountEntitlement`, `AccountProduct`, `AccountSubscription` | `system.admin` | SUMIT (`AccountPaymentMethodManager`), `SubscriptionManager` |
| System products | `System\Products\Index`, `Show`, `CreateProductWizard` | `Product`, `ProductEntitlement`, `ProductFeature`, `ProductPlan`, `ProductPrice`, `ProductLimit` | `system.admin` | `ProductIntegrityChecker` |
| System settings | `System\Settings\Index` | — (Spatie Settings) | `system.admin` | SUMIT config, Twilio config, Gemini config |
| `POST /system/impersonate/{org}` | `System\SystemImpersonationController` | `Organization`, `User` | `system.admin` | `SystemAuditLogger`; `OrganizationContext` |
| `POST /system/impersonation/exit` | `System\SystemImpersonationExitController` | `User` | auth | `SystemAuditLogger` |
| `POST /system/accounts/{account}/payment-methods` | `System\AccountPaymentMethodController@store` | `Account` | `system.admin` | SUMIT |

---

### A.9 Twilio / Voice RSVP

| Route | Controller | Auth | Integrations |
|---|---|---|---|
| `GET /twilio/calling` | `Twilio\CallingController@index` | auth | — |
| `POST /twilio/calling/initiate` | `Twilio\CallingController@call` | auth | `CallingService`, Twilio Voice API |
| `POST /twilio/calling/status` | `Twilio\CallingController@statusCallback` | none (Twilio sig) | Twilio |
| `GET /twilio/calling/logs` | `Twilio\CallingController@getLogs` | auth | — |
| `GET|POST /twilio/rsvp/connect` | `Twilio\RsvpVoiceController@connect` | none | TwiML response |
| `POST /twilio/rsvp/response` | `Twilio\RsvpVoiceController@digitResponse` | none | Twilio DTMF |
| `POST /api/twilio/rsvp/process` | `Twilio\RsvpVoiceController@process` | CALL_LOG_SECRET header | `RsvpResponse`, `Invitation` |
| `POST /api/twilio/calling/log` | `Twilio\CallingController@appendLog` | CALL_LOG_SECRET header | — |
| `GET|POST /mvp-rsvp/webhook/callcomes` | `TwilioController@callComes` | none | Twilio |

---

### A.10 Profile

| Route | Livewire Component | Models | Auth |
|---|---|---|---|
| `GET /profile` | `Profile\UpdateProfileInformationForm`, `UpdatePasswordForm`, `DeleteUserForm`, `ManagePasskeys` | `User`, `webauthn_credentials` | auth |

---

## Part B — State Flow Maps

> For each critical flow: entry → middleware → validation → business logic → side effects → redirects → events → external calls.

---

### B.1 Password Login Flow

```
Entry:      GET /login → auth.login (Blade view)
            passkey-login.js loaded via Vite

Middleware: web, guest

Validation: POST /login
            email: required|email
            password: required

Business Logic:
  1. Auth::attempt(email, password, remember)
     → FAIL: ValidationException('auth.failed')
  2. user->is_disabled check
     → TRUE: Auth::logout() + session invalidate + ValidationException('disabled')
  3. user->update(last_login_at)
  4. session()->regenerate()
  5. webAuthnCredentials()->count() === 0 → session()->flash('passkey_upgrade', true)

Redirects:
  is_system_admin → route('system.dashboard')
  else            → redirect()->intended(route('dashboard'))

Side effects:
  - last_login_at updated in DB
  - passkey_upgrade flash → layouts/app.blade.php shows upgrade banner

External calls: none
Events fired: none
```

---

### B.2 Passkey Login Flow

```
Entry:      GET /login → auth.login
            Conditional mediation starts immediately (background, no user action)
            OR user clicks "Sign in with Passkey"

JS (passkey-login.js):
  1. PublicKeyCredential.isConditionalMediationAvailable()
     → TRUE: Webpass.assert(..., { mediation: 'conditional' }) [background]
  2. Button click: Webpass.assert('/webauthn/login/options', '/webauthn/login')

POST /webauthn/login/options (throttle:webauthn 10/min per IP)
  → AssertionRequest::toVerify(email?) → returns JSON challenge
  → Challenge stored in session

POST /webauthn/login (throttle:webauthn)
  → AssertedRequest::login()
    - Verifies signature against public key in webauthn_credentials
    - Checks challenge, origin (kalfa.me), credential ID
  → Audit log: passkey.login {event_version, request_id, flow_stage, outcome, user_id, credential_hash, ip, ua_hash}
  → Returns 204 (success) or 422 (fail)

JS on 204:
  window.location.href = data-redirect (session('url.intended') or /dashboard)

Error handling:
  AbortError / NotAllowedError → console.warn, no UI banner (silent cancel)
  Other errors → show error banner

External calls: none (all crypto local)
Events fired: none
Side effects: session login, last_login_at NOT updated (only password flow updates it)
```

---

### B.3 Checkout / Payment Flow

```
Entry:      Dashboard event show → "Activate Event" button
            → GET /checkout/{organization}/{event}

Middleware: web, auth, EnsureOrganizationSelected
Policy:     EventPolicy@initiatePayment

Step 1 — Tokenization page (GET /checkout/{org}/{event}):
  Controller: CheckoutTokenizeController
  Actions:
    - Creates Sanctum token: user->createToken('checkout-tokenize')
    - Passes to view: organization, event, plan, apiUrl, bearerToken, companyId, publicKey
  View: checkout/tokenize.blade.php
    - Loads PaymentsJS (external OfficeGuy CDN script)
    - PaymentsJS renders card form in iframe
    - On submit: PaymentsJS returns single-use token (never raw card data)

Step 2 — API call (POST /api/.../checkout):
  Request: InitiateCheckoutRequest
    Validation: plan_id required; token optional
    Security: prepareForValidation() REJECTS if request contains: card_number, cvv, expiry, etc.

  Token flow (token present):
    BillingService::initiateEventPaymentWithToken(event, plan, token)
    → Creates EventBilling + Payment records
    → Event status: Draft → PendingPayment
    → Calls SumitPaymentGateway::chargeWithToken()
    → Returns: { status: 'processing', payment_id }

  Redirect flow (no token):
    BillingService::initiateEventPayment(event, plan)
    → Creates EventBilling + Payment records
    → Event status: Draft → PendingPayment
    → Calls SumitPaymentGateway::createOneTimePayment()
    → Returns: { redirect_url }

Step 3 — Webhook (POST /api/webhooks/{gateway}):
  Auth: HMAC-SHA256 via BILLING_WEBHOOK_SECRET
  Idempotency: skips if payment already in terminal state
  On success:
    BillingService::markPaymentSucceeded()
    → Payment status: Succeeded
    → EventBilling status: Paid
    → Event status: PendingPayment → Active
  On failure:
    BillingService::markPaymentFailed()
    → Payment/EventBilling/Event → Failed states

Step 4 — Status page (GET /checkout/status/{payment}):
  Policy: PaymentPolicy@view
  View polls or renders final state

External calls: SUMIT/OfficeGuy API, PaymentsJS CDN
Events fired: none (no ShouldBroadcast on payment events)
Side effects: EventBilling, Payment, Event records; potential email (not yet implemented)
```

---

### B.4 Public RSVP Flow (Web)

```
Entry:      Invitation link: GET /rsvp/{slug}

Middleware: web (no auth)

Controller: PublicRsvpViewController@show
  1. Invitation::where('slug', slug)->with(['event', 'guest'])->firstOrFail()
  2. event->status !== Active → return 404 view (rsvp/event-not-available)
  3. return rsvp.show with: invitation, EventLinks

View: rsvp/show.blade.php
  - Renders guest name, event details
  - Calendar links (Google, iCal, Outlook) via spatie/calendar-links
  - RSVP form: response (Attending/Declining/Maybe), notes, dietary etc.

POST /rsvp/{slug}/responses
Validation: StoreRsvpResponseRequest
  - response: required|enum(RsvpResponseType)
  - notes: nullable|string

Business Logic (DB transaction):
  1. Re-fetch invitation + event; check Active status
  2. RsvpResponse::updateOrCreate(invitation_id, guest_id → response data + ip + user_agent)
  3. Invitation::update(status → Responded, responded_at → now())

Redirects: redirect()->route('rsvp.show', slug)->with('success', true)

Side effects:
  - RsvpResponse record created/updated
  - Invitation status updated
  - RsvpReceived event FIRED → ShouldBroadcast → PrivateChannel('event.{id}')
    → Reverb broadcasts to dashboard listeners (EventGuests, EventInvitations)

External calls: none
Events fired: RsvpReceived (broadcast)
```

---

### B.5 Seating Assignment Flow

```
Entry:      GET /dashboard/events/{event}/tables
            GET /dashboard/events/{event}/seat-assignments

Middleware: web, auth, EnsureOrganizationSelected
Policy:     EventPolicy@view / EventPolicy@update

EventTables component:
  State: showForm, editingId, name, capacity, viewMode (list|chart)
  Actions:
    - openCreate / openEdit / save / deleteTable / cancelForm
    - handleSort(id, newPosition):
        Re-indexes sort_order for all event tables
  Sort mechanism: wire:sort directive (Livewire 4)
    → Livewire handles SortableJS initialization natively
    → Calls $wire.handleSort(id, newPosition) on drag end
  Authorization: $this->authorize('update', $this->event) on every write action

EventSeatAssignments component:
  State: assignments[] — map of guest_id → event_table_id (or '' for unassigned)
  On mount: loads all existing SeatAssignments into assignments[]
  save():
    For each guest:
      - Empty table: delete SeatAssignment
      - Valid table: SeatAssignment::updateOrCreate(event_id, guest_id → event_table_id)

Models touched: EventTable, SeatAssignment, Guest
External calls: none
Events: none
```

---

### B.6 System Admin — Impersonation Flow

```
Entry:      GET /system/organizations → click "Impersonate"

Middleware: web, auth, EnsureSystemAdmin (is_system_admin flag)
Policy:     user->can('impersonate-users') [Spatie permission]

POST /system/impersonate/{organization}
  1. Store in session:
       impersonation.original_admin_id = user->id
       impersonation.original_organization_id = user->current_organization_id
       impersonation.started_at = now()->timestamp
  2. user->update(current_organization_id = organization->id)
  3. Session::put('active_organization_id', organization->id)
  4. SystemAuditLogger::log(action: 'impersonation.started', ...)
  5. redirect()->route('dashboard')

During impersonation:
  - ImpersonationExpiry middleware: on every request checks started_at + 3600s
    → expired: auto-restore + redirect to system dashboard
  - User::currentOrganization() bypasses membership check (uses session org directly)
  - Navbar shows "Exit Impersonation" button (reads session('impersonation.original_organization_id'))

POST /system/impersonation/exit
  1. Restore: user->update(current_organization_id = original_organization_id)
  2. Flush impersonation.* session keys
  3. SystemAuditLogger::log(action: 'impersonation.ended', ...)
  4. redirect()->route('system.dashboard')

Side effects: current_organization_id changed in DB (not just session)
External calls: none
Events: none
```

---

## Part C — Frontend Dependency Graph

### C.1 Alpine.js Dependencies

| Component / View | Alpine Usage | Directives Used |
|---|---|---|
| `components/dynamic-navbar` | Mobile drawer, org dropdown, profile dropdown, impersonation dropdown | `x-data`, `@click.away`, `x-show`, `x-cloak`, `x-id`, `x-transition` |
| `components/modal` | Show/hide modal state | `x-data`, `x-show`, `x-transition`, `@keydown.escape` |
| `components/dark-mode-toggle` | Dark/light toggle + localStorage | `x-data`, `@click`, `x-init` |
| `components/action-message` | Flash message auto-dismiss (2s timeout) | `x-data`, `x-show`, `x-transition` |
| `components/file-upload-modern` | Drag-and-drop file upload UI | `x-data="modernFileUpload({...})"`, `x-init`, `@dragover`, `@drop` |
| `components/tree/⚡tree-toolbar` | Settings panel toggle | `x-data="{ settingsOpen: false }"` |
| `livewire/tree-branch` | Expand/collapse tree node | `x-data="{ open: true|false }"`, `@alpinejs/collapse` |
| `livewire/tree-node` | Inline edit state for tree items | `x-data`, `x-show`, `@click.outside` |
| `livewire/system/products/product-tree` | Drag sort + inline edit | `x-data`, `x-init` (hosts `Sortable.create()`), `$wire` calls |
| `livewire/system/settings/index` | Tab switching synced to Livewire | `x-data="{ activeTab: @entangle('activeTab') }"` |
| `livewire/profile/delete-user-form` | Confirm delete modal | `x-data=""`, `x-show` |
| `layouts/app` | Passkey upgrade banner dismiss | `x-data="{ show: true }"`, `@click` |
| `auth/login` | (none — Alpine not used in login form) | — |

**Alpine plugins used:**
- `@alpinejs/collapse` — tree nodes (TreeBranch)
- `@alpinejs/intersect` — lazy-load triggers (grep found in views but no specific component identified)

---

### C.2 SortableJS Dependencies

| View | Type | Initialization | Calls back to |
|---|---|---|---|
| `livewire/dashboard/event-tables` | `wire:sort` directive (Livewire 4 native) | Livewire handles SortableJS init internally | `$wire.handleSort(id, newPosition)` |
| `livewire/system/products/product-tree` | Direct `Sortable.create($el, {...})` inside Alpine `x-init` | Alpine `x-init` initializes on mount | `$wire.reorderPlans([...ids])` |
| `livewire/system/products/create-product-wizard` | Direct `Sortable.create($el, {...})` inside Alpine `x-init` | Alpine `x-init` initializes on mount | `$wire.reorderPlans([...ids])` |

**Key distinction:**
- `wire:sort` (event-tables): Livewire 4 built-in — uses SortableJS internally, no direct call
- `Sortable.create()` (product views): direct usage — depends on `window.Sortable` global being available

**Migration note:** All three must be re-implemented with `@dnd-kit/core` or `react-beautiful-dnd` in React. The `wire:sort` pattern has no Inertia equivalent.

---

### C.3 Echo / Reverb Realtime Dependencies

| Livewire Component | Listener | Channel | Event Class |
|---|---|---|---|
| `Dashboard\EventGuests` | `#[On('echo-private:event.{event.id},RsvpReceived')]` | `private:event.{id}` | `App\Events\RsvpReceived` |
| `Dashboard\EventInvitations` | `#[On('echo-private:event.{event.id},RsvpReceived')]` | `private:event.{id}` | `App\Events\RsvpReceived` |

Channel authorization (`routes/channels.php`):
```php
Broadcast::channel('event.{id}', function ($user, $id) {
    $event = Event::find($id);
    return $user->organizations->contains('id', $event->organization_id);
});
```

**Migration note:** In React + Inertia, these become `useEcho` hooks subscribing to the same private channels.

---

### C.4 Session Flash Dependencies

| Livewire Component | Flash Key | Consumed by |
|---|---|---|
| `LoginController` | `passkey_upgrade` | `layouts/app.blade.php` → shows Passkey upgrade banner |
| `System\Accounts\Show` | `success` | View renders `session('success')` message |
| `System\Accounts\CreateAccountWizard` | `message` | View renders flash |
| `System\Users\Show` | `success` | View renders flash |
| `System\Settings\Index` | `success`, `error` | View renders flash |
| `System\Organizations\Show` | `success` | View renders flash |
| `SystemImpersonationController` | `impersonation.*` (session put, not flash) | `ImpersonationExpiry` middleware, navbar |

**Migration note:** In Inertia, session flash is handled via `HandleInertiaRequests::share()` which exposes `flash` as a shared prop. All flash consumers become React components reading `usePage().props.flash`.

---

### C.5 Policy Gate Dependencies (Livewire)

| Component | Policy Checks | Gate Method |
|---|---|---|
| `Dashboard\EventTables` | `view`, `update` on Event | `$this->authorize()` on every action |
| `Dashboard\EventGuests` | `update` on Event, `update` on Guest | `$this->authorize()` |
| `Dashboard\EventSeatAssignments` | `view`, `update` on Event | `$this->authorize()` |
| `Dashboard\EventInvitations` | `update` on Event | `$this->authorize()` |
| `Billing\AccountOverview` | `update` on Organization | `$this->authorize()` |
| `Billing\EntitlementsIndex` | `view` on Organization | `$this->authorize()` |
| Blade views | `@can('update', $event)` | Used to show/hide drag handle in event-tables |

**Migration note:** In React/Inertia, policy results are passed via Inertia props (computed server-side in the controller). The `@can` blade directives become conditional JSX. `authorize()` calls move to controller actions, not component methods.

---

## Part D — Echo.js / Reverb Gap — Validated Finding

### D.1 Server-side: Reverb ✅

```
.env:   BROADCAST_CONNECTION=reverb
        REVERB_APP_ID=849312
        REVERB_HOST=kalfa.me
        REVERB_PORT=443
        REVERB_SCHEME=https

config/broadcasting.php:  'default' => env('BROADCAST_CONNECTION', 'null')
                          'reverb' driver fully configured
```

Server broadcasts `RsvpReceived` via **Reverb** WebSocket server on port 443.

### D.2 Client-side: Pusher ❌

```javascript
// resources/js/echo.js — CURRENT STATE
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",              // ← Pusher, not reverb
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    wsHost: import.meta.env.VITE_PUSHER_HOST,  // ← resolves from PUSHER_HOST env
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT,
    enabledTransports: ["ws", "wss"],
});
```

`.env` has both sets of VITE vars:
```
VITE_PUSHER_APP_KEY=3adb6d02a94cee443f36   ← populated
VITE_PUSHER_HOST="${PUSHER_HOST}"           ← resolves from PUSHER_HOST (set to Reverb host)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"     ← populated but UNUSED by echo.js
VITE_REVERB_HOST="${REVERB_HOST}"           ← populated but UNUSED by echo.js
```

### D.3 Impact Assessment

| Component | Expected behavior | Actual behavior |
|---|---|---|
| `Dashboard\EventGuests` | Real-time RSVP counter update | ❌ Not working — client connects to Pusher protocol, server uses Reverb |
| `Dashboard\EventInvitations` | Real-time status badge update | ❌ Not working — same reason |
| `RsvpReceived` broadcast | Delivered to dashboard | ❌ Event fired but not received by client |

### D.4 Root Cause

`echo.js` was written for Pusher (from Breeze scaffolding) and never updated when Reverb was installed. The `VITE_REVERB_*` env vars were added to `.env` but `echo.js` was not updated to use them.

### D.5 Fix (documented only — not implemented)

```javascript
// echo.js — CORRECTED version (not yet applied)
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

**This fix is independent of the React/Inertia migration.** It is a production bug affecting real-time RSVP updates today, on the current Blade/Livewire stack. It should be applied before or separately from the migration.

---

## Part E — Summary for Migration Planning

### E.1 Confirmed Blockers Before Migration

| Issue | Severity | Action Required |
|---|---|---|
| `echo.js` uses Pusher, server uses Reverb | 🔴 Production bug (realtime broken) | Fix `echo.js` independently — one-line change |
| `BROADCAST_CONNECTION=reverb` but no Reverb process in PM2 | 🟠 Needs verification | Check if `php artisan reverb:start` is running in production |

### E.2 Migration Surface — Prioritized

| Priority | Area | Complexity | Reason |
|---|---|---|---|
| 1 | Fix echo.js (Reverb) | 🟢 Trivial | Independent, fixes production now |
| 2 | `HandleInertiaRequests` middleware | 🟢 Low | Share: auth user, current org, impersonation state, flash, permissions |
| 3 | Auth pages (login/register/reset) | 🟢 Low | Standard Inertia forms |
| 4 | Public pages (RSVP, event show) | 🟢 Low | No auth complexity |
| 5 | Dashboard CRUD (events) | 🟡 Medium | Controllers already exist; add `Inertia::render()` |
| 6 | EventGuests + EventInvitations | 🟡 Medium | Echo hook needed in React |
| 7 | EventTables (drag-sort) | 🟡 Medium | `wire:sort` → `@dnd-kit/core` |
| 8 | EventSeatAssignments | 🟡 Medium | Grid UI + bulk save |
| 9 | Billing pages | 🟡 Medium | Pass entitlement data via Inertia props |
| 10 | Checkout / PaymentsJS | 🟠 High | External CDN JS integration; tokenization page can stay Blade |
| 11 | Profile + ManagePasskeys | 🟠 High | WebAuthn JS must stay as Vite entry or be wrapped as React component |
| 12 | System Admin panel | 🔴 High | 15+ complex Livewire components; tree, wizard, product engine |

### E.3 What Does NOT Change

- All API routes (`/api/*`) — unchanged
- All Twilio routes and TwiML views — stay as Blade (server XML)
- `server.js` Node.js voice bridge — fully independent
- All payment webhook handlers — unchanged
- All models, migrations, policies, services — unchanged
- Database schema — unchanged
- Scheduled commands — unchanged
