---
date: 2026-03-16
tags: [architecture, auth, webauthn, passkeys, middleware, session]
status: active
---

# Authentication & Authorization

## Overview

KALFA supports two authentication methods:

1. **Email + Password** — Standard Laravel session auth
2. **WebAuthn Passkeys** — FIDO2/WebAuthn passwordless login

Authorization is a dual-layer system — see [[Architecture/Permissions]] for full detail.

---

## Authentication Methods

### 1. Email + Password

Standard Laravel `Auth` with session cookies.

```
POST /login
    │
    ├── Validate credentials
    ├── Create session
    └── Redirect to dashboard
```

### 2. WebAuthn / Passkeys

FIDO2-based passwordless authentication using device biometrics or hardware keys.

**Registration Flow:**
```
User navigates to Profile → Passkeys
        │
POST /webauthn/register/options  ← WebAuthnRegisterController
        │ (returns challenge)
        ▼
Browser prompts biometric/device
        │
POST /webauthn/register          ← WebAuthnRegisterController
        │
Credential stored in DB
        │
StoreWebAuthnCredentialInSession listener
→ credential stored in session for immediate use
```

**Login Flow:**
```
GET /webauthn/login/options      ← WebAuthnLoginController
        │ (returns challenge)
        ▼
Browser signs challenge with stored credential
        │
POST /webauthn/login             ← WebAuthnLoginController
        │
Credential verified → session created
        │
Redirect to dashboard
```

| Controller | File |
|-----------|------|
| `WebAuthnLoginController` | `app/Http/Controllers/WebAuthn/WebAuthnLoginController.php` |
| `WebAuthnRegisterController` | `app/Http/Controllers/WebAuthn/WebAuthnRegisterController.php` |
| `ManagePasskeys` Livewire | `app/Livewire/Profile/ManagePasskeys.php` |

---

## Middleware Chain

Middleware is applied in layers depending on route group:

### Dashboard Routes (authenticated)

```
web (session, CSRF)
    └── auth
            └── RequireImpersonationForSystemAdmin
                    └── ImpersonationExpiry
                            └── EnsureOrganizationSelected
                                    └── SpatiePermissionTeam
```

### API Routes

```
api (stateless)
    └── auth:sanctum
            └── (no org middleware — org resolved from route param)
```

### Middleware Reference

| Middleware | Purpose |
|-----------|---------|
| `EnsureOrganizationSelected` | Redirects user if no active org (0 orgs → create, null current → select, suspended → select) |
| `SpatiePermissionTeam` | Sets Spatie permission team to `current_organization_id` |
| `ImpersonationExpiry` | Terminates expired admin impersonation sessions |
| `RequireImpersonationForSystemAdmin` | Blocks system admins from accessing tenant pages without impersonation |
| `RequestId` | Adds unique `X-Request-Id` to every request (log correlation) |
| `VerifyCsrfToken` | Standard CSRF protection |

---

## Admin Impersonation

System admins can impersonate any organization user for support purposes.

```
System Admin (no org)
        │
        └── RequireImpersonationForSystemAdmin middleware
                    │
            [must impersonate first]
                    │
                    ▼
        EnsureSystemAdmin middleware (admin-only routes)
                    │
        Impersonation session created
                    │
        ImpersonationExpiry middleware
        checks expiry on every request
                    │
        [expired] → impersonation terminated
        [active]  → user treated as org member
```

---

## API Authentication

REST API uses Laravel Sanctum token authentication:

```
Authorization: Bearer {token}
    │
    ▼
auth:sanctum middleware
    │
    ├── Validates token against personal_access_tokens table
    └── Sets authenticated user for request
```

Token issuance and management is handled via the dashboard UI.

---

## Session & Security Notes

- Sessions stored in database (`sessions` table) or Redis
- CSRF protection on all state-changing web routes
- WebAuthn credentials stored per-user with device names
- Passkey credentials temporarily cached in session post-registration (`StoreWebAuthnCredentialInSession`)
- `PasskeyAuditContext` records WebAuthn operations for audit trail

---

## Related

- [[Architecture/Permissions]] — Role-based access control and Spatie permissions
- [[Architecture/Services/OrganizationContext]] — How org context is resolved per request
- [[Architecture/Overview]] — Where auth fits in the full system
- [[Architecture/Diagrams/06-Auth-Flow]] — Visual auth flow diagram
