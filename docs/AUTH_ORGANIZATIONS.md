# Authentication & Organizations — Architecture Reference

> Auto-generated: 2026-03-18 | Source: full codebase scan of `/var/www/vhosts/kalfa.me/httpdocs`

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication Flows](#authentication-flows)
3. [WebAuthn / Passkeys](#webauthn--passkeys)
4. [Multi-Tenancy Model (Organizations)](#multi-tenancy-model-organizations)
5. [Permission & Role System](#permission--role-system)
6. [Authorization Policies](#authorization-policies)
7. [Middleware Chain](#middleware-chain)
8. [Feature Gating (Product Engine)](#feature-gating-product-engine)
9. [Impersonation System](#impersonation-system)
10. [Security Features](#security-features)
11. [Profile Management (Livewire)](#profile-management-livewire)
12. [Key Files Reference](#key-files-reference)

---

## Overview

The application is a **multi-tenant Event SaaS** built on Laravel. Authentication uses **session-based auth** with the `web` guard, enhanced with **WebAuthn/Passkeys** (via `laragear/webauthn`) and **API tokens** (via Laravel Sanctum). Multi-tenancy is implemented through an **organization membership model** — users belong to organizations via a pivot table, and a "current organization" is stored on the User model as the single source of truth.

### Core Stack

| Component | Implementation |
|---|---|
| Auth Guard | `session` (web) via `eloquent-webauthn` provider |
| Passwordless Auth | Laragear WebAuthn (FIDO2/Passkeys) |
| API Tokens | Laravel Sanctum (stateful SPA + bearer tokens) |
| Permissions | Spatie Permission (team-scoped, `organization_id` as team FK) |
| Multi-Tenancy | Organization pivot model (`organization_users` table) |
| Frontend Auth Components | Livewire (profile, passkeys, organizations) |

---

## Authentication Flows

### Registration

**Controller:** `App\Http\Controllers\Auth\RegisterController`  
**Route:** `GET/POST /register` (guest middleware)  
**Form Request:** `App\Http\Requests\Auth\StoreRegisterRequest`

1. User submits `first_name`, `last_name`, `email`, `password`, `password_confirmation`
2. Validation: unique email, min 8 char password, confirmed
3. `User::create()` with `name` = combined first+last, bcrypt password
4. Auto-login via `Auth::login($user)`
5. Redirect to `route('dashboard')`

**Note:** Email verification is not enforced at registration (MustVerifyEmail is commented out on the User model), but the verification flow exists and can be enabled.

### Login (Password)

**Controller:** `App\Http\Controllers\Auth\LoginController`  
**Route:** `GET/POST /login` (guest middleware)

1. Validate `email` + `password`
2. `Auth::attempt()` with optional `remember` token
3. **Disabled account check:** if `$user->is_disabled`, logout + session invalidate + error
4. Update `last_login_at` timestamp
5. Session regeneration
6. **Passkey upgrade prompt:** if user has zero WebAuthn credentials, flash `passkey_upgrade`
7. Redirect:
   - System admins → `route('system.dashboard')`
   - Regular users → `route('dashboard')`

### Login (Passkey / WebAuthn)

**Provider:** `eloquent-webauthn` (config/auth.php) with `password_fallback: true`  
**Package:** `laragear/webauthn`

The WebAuthn login uses the standard Laragear endpoints:
- `POST /webauthn/login/options` — generate assertion challenge
- `POST /webauthn/login` — verify assertion response

The UX follows a **user-initiated model** (my.gov.il-style):
- Login page loads without any automatic biometric/passkey prompt
- User explicitly clicks a "Sign in with Passkey" button
- Only then does the WebAuthn ceremony begin

On successful assertion, the `CredentialAsserted` event fires, and a listener (`StoreWebAuthnCredentialInSession`) stores the credential ID in session at `webauthn.current_credential_id`.

### Logout

**Controller:** `App\Http\Controllers\Auth\LogoutController`  
**Route:** `POST /logout` (auth middleware)

1. `Auth::guard('web')->logout()`
2. Clears `webauthn.current_credential_id` from session
3. Invalidates session + regenerates CSRF token
4. Redirects to `/`

### Password Reset

**Controller:** `App\Http\Controllers\Auth\PasswordController`  
**Routes (guest middleware):**
- `GET /forgot-password` → show form
- `POST /forgot-password` → send reset link (name: `password.email`)
- `GET /reset-password/{token}` → show reset form
- `POST /reset-password` → update password (name: `password.store`)

Standard Laravel password reset: 60-minute token expiry, 60-second throttle.

### Password Confirmation

**Controller:** `App\Http\Controllers\Auth\ConfirmPasswordController`  
**Route:** `GET/POST /confirm-password` (auth middleware)

Used for sensitive actions. Validates `current_password`, then calls `$request->session()->passwordConfirmed()`.

### Email Verification

**Controllers:** `VerificationController` + `VerifyEmailController`  
**Routes (auth middleware):**
- `GET /verify-email` → notice page
- `POST /verify-email` → resend verification (name: `verification.send`)
- `GET /verify-email/{id}/{hash}` → verify link (signed URL, throttle: 6/min)

Uses Laravel's built-in `EmailVerificationRequest`. Fires `Verified` event on success. Redirects to dashboard with `?verified=1`.

---

## WebAuthn / Passkeys

### Configuration (`config/webauthn.php`)

| Setting | Value |
|---|---|
| RP Name | `env('WEBAUTHN_NAME')` (falls back to `app.name`) |
| RP ID | `env('WEBAUTHN_ID')` — must match exact domain |
| Origins | `env('WEBAUTHN_ORIGINS')` — comma-separated |
| Challenge bytes | 16 |
| Challenge timeout | 120 seconds |
| Session key | `_webauthn` |

### User Model Integration

```php
class User extends Authenticatable implements WebAuthnAuthenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, WebAuthnAuthentication;
}
```

The auth provider is `eloquent-webauthn` with `password_fallback: true`, meaning both password and passkey login work through the same guard.

### Passkey Management (Livewire)

**Component:** `App\Livewire\Profile\ManagePasskeys`

- Lists user's WebAuthn credentials (max 25 displayed)
- Register new passkeys (via browser WebAuthn API)
- Rename passkeys (alias, max 64 chars)
- Delete passkeys (with structured logging)
- Max 10 passkeys per user (`MAX_PASSKEYS = 10`)
- Displays the currently-authenticated credential (from session)
- **Device detection:** Resolves AAGUID → human-readable names (Touch ID, Windows Hello, YubiKey variants, 1Password, Google Password Manager, iCloud Keychain, etc.)
- Fallback detection via transports array (internal, usb, nfc, ble)

### Rate Limiting

```php
RateLimiter::for('webauthn', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
```

### Event Handling

```php
Event::listen(CredentialAsserted::class, StoreWebAuthnCredentialInSession::class);
```

---

## Multi-Tenancy Model (Organizations)

### Data Model

```
users ─── organization_users (pivot) ─── organizations ─── accounts
          ├── user_id                     ├── account_id
          ├── organization_id             ├── name
          └── role (enum)                 ├── slug
                                          ├── billing_email
                                          ├── settings (JSON)
                                          └── is_suspended
```

### Organization-User Relationship

**Pivot Model:** `App\Models\OrganizationUser` (extends `Pivot`)  
**Table:** `organization_users`

| Column | Type | Description |
|---|---|---|
| `organization_id` | FK | Organization |
| `user_id` | FK | User |
| `role` | enum string | `owner`, `admin`, `member` |

### OrganizationUserRole Enum

```php
enum OrganizationUserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
}
```

### Current Organization (Source of Truth)

The **User model** holds `current_organization_id` as the **single source of truth**. The session key `active_organization_id` is a mirror only — never the primary source.

```php
// User model — PHP 8.4 property hook
public ?Organization $currentOrganization {
    get {
        // 1. Find org by current_organization_id
        // 2. System admin + impersonation → return directly (skip membership)
        // 3. Normal users → verify membership exists
        // 4. Return null if no match
    }
}
```

### OrganizationContext Service

**Class:** `App\Services\OrganizationContext`

| Method | Description |
|---|---|
| `set(Organization)` | Validates membership, writes to `User.current_organization_id` + session |
| `setById(int)` | Same as `set()` but takes ID |
| `current()` | Returns `$user->currentOrganization` (DB-based, never session) |
| `clear()` | Forgets session key |
| `validateMembership()` | Checks pivot table membership |

### Organization Creation (Livewire)

**Component:** `App\Livewire\Organizations\Create`

1. Validates `name` (required, max 255)
2. Creates Organization with auto-generated unique slug
3. Attaches user as `Owner` in pivot
4. Sets as current organization
5. Sends welcome email (fire-and-forget, catches errors)
6. Redirects to dashboard

### Organization Selection (Livewire)

**Component:** `App\Livewire\Organizations\Index`

- **0 orgs →** redirect to `organizations.create`
- **1 org + none selected →** auto-select and redirect to dashboard
- **Multiple orgs →** show selection page with event counts
- **System admins →** pass through (no auto-redirect)

### Organization Invitations

**Model:** `App\Models\OrganizationInvitation`

| Column | Type | Description |
|---|---|---|
| `organization_id` | FK | Target org |
| `email` | string | Invitee email |
| `role` | enum | Role to assign |
| `token` | string(64) | Random acceptance token |
| `expires_at` | datetime | 7-day expiry |

**Service:** `App\Services\OrganizationMemberService`

| Method | Description |
|---|---|
| `invite()` | Delete existing pending invite, create new with 7-day token, send email |
| `acceptInvitation()` | Validate token + expiry, add member, auto-set current org if none, delete invite |
| `addMember()` | Direct add (for system admin), syncs Spatie role |
| `removeMember()` | Prevents removing sole owner, detaches user, clears Spatie roles, resets current org |
| `updateRole()` | Updates pivot role + syncs Spatie role |

### Spatie Role Mapping

The `OrganizationMemberService` maps pivot roles to Spatie team-scoped roles:

| Pivot Role | Spatie Role |
|---|---|
| `Owner` | `Organization Admin` |
| `Admin` | `Organization Admin` |
| `Member` | `Organization Editor` |

---

## Permission & Role System

### Spatie Permission Configuration (`config/permission.php`)

| Setting | Value |
|---|---|
| Teams enabled | `true` |
| Team foreign key | `organization_id` |
| Wildcard permissions | `false` |
| Cache | 24 hours, auto-flush on change |

### Team Scope Middleware

**`SpatiePermissionTeam`** middleware runs on every web request:
```php
app(PermissionRegistrar::class)->setPermissionsTeamId($user->current_organization_id);
```

This ensures all `$user->can()` / `$user->hasRole()` checks are scoped to the current organization.

### Gate::before (System Admin Bypass)

Registered in `AppServiceProvider::boot()`:

```php
Gate::before(function ($user, $ability) {
    if (!$user->is_system_admin) return null;

    // System-level abilities: always granted
    $systemAbilities = [
        'manage-system', 'manage-organizations', 'manage-users',
        'impersonate-users', 'viewPulse', 'viewTelescope',
    ];
    if (in_array($ability, $systemAbilities)) return true;

    // Tenant abilities: only if impersonating
    if (session()->has('impersonation.original_organization_id')) return true;

    return null; // Let policy decide
});
```

### Tenant Permissions (Billing-Gated)

**Service:** `App\Services\PermissionSyncService`

Syncs these permissions for org Owner/Admin users when billing is active:

- `view-event-details`
- `manage-event-guests`
- `manage-event-tables`
- `send-invitations`

**Grant trigger:** AccountProduct becomes active + succeeded payment OR admin-granted product  
**Revoke trigger:** No more qualifying active products

### Feature Gate

```php
Gate::define('feature', function ($user, string $featureKey): bool {
    $account = $user->currentOrganization?->account;
    return app(FeatureResolver::class)->allows($account, $featureKey);
});
```

Feature keys are defined in `App\Enums\Feature` (e.g., `twilio_enabled`, `voice_rsvp_calls`, `create_event`, `max_guests_per_event`, etc.).

---

## Authorization Policies

### OrganizationPolicy

| Method | Who Can |
|---|---|
| `view` | Any org member |
| `update` | Owner or Admin |
| `initiateBilling` | Owner or Admin |
| `manageBilling` | System admin OR Owner/Admin |
| `manageMembers` | Owner or Admin |

### EventPolicy

| Method | Who Can |
|---|---|
| `viewAny` | Any org member |
| `view` | Org member + `view-event-details` permission |
| `create` | Org member + `manage-event-guests` permission |
| `update` | Org member + `manage-event-guests` permission |
| `delete` | Any org member |
| `initiatePayment` | Delegates to `OrganizationPolicy::initiateBilling` |

### GuestPolicy

| Method | Who Can |
|---|---|
| `viewAny` / `view` / `create` / `update` / `delete` | Any member of the guest's event's organization |

### PaymentPolicy

| Method | Who Can |
|---|---|
| `view` | Any member of the payment's organization |

---

## Middleware Chain

### Global Web Middleware (appended)

Applied to **all** web requests in order:

1. **`RequestId`** — Attaches/propagates `X-Request-Id` UUID for logging correlation
2. **`ImpersonationExpiry`** — Auto-expires impersonation sessions after 60 minutes; restores original org; logs audit event
3. **`SpatiePermissionTeam`** — Sets Spatie permission team ID to `$user->current_organization_id`

### CSRF Customization

**`VerifyCsrfToken`** — Excludes webhook endpoints:
- `officeguy/webhook/*`
- `mvp-rsvp/webhook/*`
- `twilio/*`

CSRF `TokenMismatchException` is handled gracefully:
- JSON requests → 419 JSON response
- Web requests → redirect to login with "session expired" message

### Route Middleware Aliases

| Alias | Class | Purpose |
|---|---|---|
| `ensure.organization` | `EnsureOrganizationSelected` | Requires user to have/select an active organization |
| `ensure.account_active` | `EnsureAccountActive` | Gates tenant routes behind active billing (product/subscription/trial) |
| `ensure.feature` | `EnsureFeatureAccess` | Gates routes behind specific product feature entitlements |
| `system.admin` | `EnsureSystemAdmin` | Requires `manage-system` ability (system admin only) |
| `require.impersonation` | `RequireImpersonationForSystemAdmin` | Blocks system admins from tenant routes without impersonation |

### Middleware Details

#### EnsureOrganizationSelected

- Skips unauthenticated users
- Allows `organizations.*` routes (list/create/switch) without redirect
- **0 orgs →** redirect to `organizations.create`
- **No current org →** redirect to `organizations.index`
- **Suspended org →** redirect to index with error

#### EnsureAccountActive

Passes through for:
- System admins
- Impersonating admins (session key present)
- Accounts with active `AccountProduct`
- Accounts with active `Subscription`
- Accounts on active `Trial`

Deny: HTTP 402 JSON for API, redirect to `billing.account` for web.

#### EnsureFeatureAccess

Usage: `Route::middleware('ensure.feature:twilio_enabled')`

- System admins bypass
- Impersonating admins bypass
- Otherwise checks `Gate::allows('feature', $featureKey)`
- Deny: HTTP 403 JSON for API, redirect to billing with reason param for web

#### EnsureSystemAdmin

Simple gate check: `auth()->user()->can('manage-system')` → 403 on failure.

#### RequireImpersonationForSystemAdmin

Prevents system admins from accessing tenant-scoped routes directly. Normal users pass through. System admins must have `impersonation.original_organization_id` in session.

---

## Feature Gating (Product Engine)

The `Feature` enum defines all product-engine feature keys:

| Category | Features |
|---|---|
| **Voice/Calling** | `twilio_enabled`, `voice_rsvp_calls` |
| **SMS** | `sms_confirmation_enabled`, `sms_confirmation_limit`, `sms_confirmation_messages` |
| **Events** | `create_event`, `max_active_events` |
| **Guests** | `max_guests_per_event`, `guest_import` |
| **Seating** | `seating_management` |
| **Invitations** | `invitation_sending` |

Features are resolved via `FeatureResolver` against `account_entitlements` / `product_entitlements` tables, with system admin and impersonation bypasses handled at the Gate::before level.

---

## Impersonation System

System admins can impersonate organizations (not individual users) for support/debugging.

### Session Keys

| Key | Purpose |
|---|---|
| `impersonation.original_organization_id` | Admin's home org to restore on exit |
| `impersonation.started_at` | Unix timestamp for timeout |
| `impersonation.original_admin_id` | Audit trail |

### Behavior

- **Timeout:** 60 minutes (hard limit via `ImpersonationExpiry` middleware)
- **On expiry:** Restores original org, clears session, redirects to `system.dashboard`, logs audit event
- **Bypasses:** Billing gates, feature gates, tenant permission checks (via `Gate::before`)
- **Restriction:** `RequireImpersonationForSystemAdmin` blocks system admins from accessing tenant routes without active impersonation

### Audit Logging

Uses `SystemAuditLogger::log()` with structured metadata:
```php
['expired' => true, 'restored_organization_id' => $originalOrgId, 'duration_minutes' => ...]
```

---

## Security Features

### Session Security

- **Session regeneration** on login (`$request->session()->regenerate()`)
- **Session invalidation** on logout (full invalidate + token regenerate)
- **CSRF protection** on all web routes (except webhook endpoints)
- **Graceful CSRF expiry** — 419 JSON for API, redirect for web with user-friendly message

### Password Security

- Passwords hashed via `bcrypt` (with `hashed` cast for double-hashing prevention)
- Minimum 8 characters on registration and reset
- `current_password` validation on password change and account deletion
- Reset tokens expire in 60 minutes with 60-second throttle
- Password confirmation timeout: 3 hours (10800 seconds)

### Account Security

- **Account disabling:** `is_disabled` flag checked on login; disabled users are immediately logged out
- **Organization suspension:** `is_suspended` flag redirects users away from suspended orgs

### WebAuthn/Passkeys Security

- FIDO2 standard with cryptographic challenge-response (16 bytes randomness)
- Challenge timeout: 120 seconds
- Rate limited: 10 requests/minute per IP
- RP ID and origins configured via environment
- Credential ID stored in session for tracking which passkey was used
- Structured logging on passkey deletion (hashed credential ID, IP, user agent hash)

### API Security (Sanctum)

- Stateful SPA authentication with configurable stateful domains
- Guards: `['web']`
- Token expiration: `null` (no auto-expiry, managed per-token)
- CSRF validation middleware integrated

### Monitoring & Observability

- **Request ID** propagation (`X-Request-Id` header) for log correlation
- **Telescope** access gated to system admins (`viewTelescope`)
- **Pulse** access gated to system admins (`viewPulse`)
- **Structured logging** for auth events (passkey registration/deletion)
- **SystemAuditLogger** for impersonation events

---

## Profile Management (Livewire)

### UpdateProfileInformationForm

- Edit `name` and `email`
- Email change resets `email_verified_at` to null
- Can trigger re-verification email

### UpdatePasswordForm

- Requires `current_password`
- Validates against `Password::defaults()`
- Must confirm new password
- Resets form fields after success

### ManagePasskeys

- List, register, rename, delete WebAuthn credentials
- Device identification via AAGUID mapping
- Shows which passkey authenticated the current session
- Max 10 passkeys per user

### DeleteUserForm

- Requires `current_password` confirmation
- Logs out user, deletes account
- Redirects to `/`

---

## Key Files Reference

### Controllers
| File | Purpose |
|---|---|
| `app/Http/Controllers/Auth/LoginController.php` | Password login |
| `app/Http/Controllers/Auth/RegisterController.php` | User registration |
| `app/Http/Controllers/Auth/LogoutController.php` | Session logout |
| `app/Http/Controllers/Auth/PasswordController.php` | Forgot/reset password |
| `app/Http/Controllers/Auth/ConfirmPasswordController.php` | Password confirmation for sensitive actions |
| `app/Http/Controllers/Auth/VerificationController.php` | Email verification notice/resend |
| `app/Http/Controllers/Auth/VerifyEmailController.php` | Email verification link handler |

### Middleware
| File | Alias | Purpose |
|---|---|---|
| `app/Http/Middleware/EnsureOrganizationSelected.php` | `ensure.organization` | Requires active org |
| `app/Http/Middleware/EnsureAccountActive.php` | `ensure.account_active` | Billing gate |
| `app/Http/Middleware/EnsureFeatureAccess.php` | `ensure.feature` | Feature entitlement gate |
| `app/Http/Middleware/EnsureSystemAdmin.php` | `system.admin` | System admin gate |
| `app/Http/Middleware/RequireImpersonationForSystemAdmin.php` | `require.impersonation` | Block admin tenant access without impersonation |
| `app/Http/Middleware/ImpersonationExpiry.php` | (global) | Auto-expire impersonation sessions |
| `app/Http/Middleware/SpatiePermissionTeam.php` | (global) | Set Spatie team scope |
| `app/Http/Middleware/RequestId.php` | (global) | X-Request-Id propagation |
| `app/Http/Middleware/VerifyCsrfToken.php` | (replaced) | CSRF with webhook exclusions |

### Models
| File | Purpose |
|---|---|
| `app/Models/User.php` | User with WebAuthn, Sanctum, Spatie roles |
| `app/Models/Organization.php` | Tenant entity with billing |
| `app/Models/OrganizationUser.php` | Pivot with role enum |
| `app/Models/OrganizationInvitation.php` | Invite tokens with expiry |

### Services
| File | Purpose |
|---|---|
| `app/Services/OrganizationContext.php` | Current org management (DB as source of truth) |
| `app/Services/OrganizationMemberService.php` | Member CRUD, invitations, role sync |
| `app/Services/PermissionSyncService.php` | Billing-gated permission grants/revokes |

### Policies
| File | Purpose |
|---|---|
| `app/Policies/OrganizationPolicy.php` | Org view/update/billing/member management |
| `app/Policies/EventPolicy.php` | Event CRUD with permission checks |
| `app/Policies/GuestPolicy.php` | Guest CRUD scoped to org membership |
| `app/Policies/PaymentPolicy.php` | Payment view scoped to org |

### Config
| File | Key Settings |
|---|---|
| `config/auth.php` | `eloquent-webauthn` provider, `password_fallback: true` |
| `config/permission.php` | Teams: `true`, team FK: `organization_id` |
| `config/webauthn.php` | RP config, 120s challenge timeout |
| `config/sanctum.php` | Stateful domains, web guard |

### Routes
| File | Purpose |
|---|---|
| `routes/auth.php` | All auth routes (login, register, password, verification) |

### Livewire
| File | Purpose |
|---|---|
| `app/Livewire/Profile/UpdateProfileInformationForm.php` | Edit name/email |
| `app/Livewire/Profile/UpdatePasswordForm.php` | Change password |
| `app/Livewire/Profile/ManagePasskeys.php` | Passkey CRUD |
| `app/Livewire/Profile/DeleteUserForm.php` | Account deletion |
| `app/Livewire/Organizations/Create.php` | New org creation |
| `app/Livewire/Organizations/Index.php` | Org list/selection |

### Enums
| File | Values |
|---|---|
| `app/Enums/OrganizationUserRole.php` | `owner`, `admin`, `member` |
| `app/Enums/Feature.php` | 14 feature keys for product engine |

### Bootstrap
| File | Key Config |
|---|---|
| `bootstrap/app.php` | Middleware aliases, global web middleware, CSRF exception handling |
