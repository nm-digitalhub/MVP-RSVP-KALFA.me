---
date: 2026-03-16
tags: [architecture, overview, multi-tenant, laravel]
status: active
---

# KALFA — System Overview

## Platform Purpose

**KALFA** is a multi-tenant RSVP + Seating SaaS application for Israeli event organizers. It handles guest invitations, RSVP collection (web, SMS, WhatsApp, voice), seating assignments, and event payments.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.4) |
| Frontend | Livewire 4 + Alpine.js |
| Styling | Tailwind CSS v4 + Flowbite 4 |
| Build | Vite 7 |
| Auth | Laravel Sanctum + Passkeys (WebAuthn via Laragear) |
| Database | PostgreSQL (production) / SQLite (tests) |
| Queue | Redis |
| Payments | SUMIT gateway (`officeguy/laravel-sumit-gateway`) |
| SMS/Voice | Twilio (Verify API + Programmable Voice) |
| AI Voice | Gemini Live API (Hebrew TTS via Node.js relay) |
| Monitoring | Laravel Telescope + Pulse |
| Language | Hebrew (RTL — `dir="rtl"` in `app.blade.php`) |

---

## Architecture Layers

```
┌─────────────────────────────────────────────┐
│               Public / Web Routes            │
│   (Login, RSVP Page, Checkout, Passkeys)     │
├─────────────────────────────────────────────┤
│         Dashboard (Livewire 4 SPA)           │
│  Events · Guests · Tables · Invitations      │
├─────────────────────────────────────────────┤
│        REST API (auth:sanctum)               │
│   /api/organizations/{org}/events/{event}/*  │
├─────────────────────────────────────────────┤
│            Service Layer                     │
│ BillingService · OrganizationContext         │
│ FeatureResolver · PermissionSyncService      │
│ CallingService · WhatsAppRsvpService         │
├─────────────────────────────────────────────┤
│         Product Engine                       │
│  Products · Plans · Entitlements · Usage     │
├─────────────────────────────────────────────┤
│         Eloquent Models / PostgreSQL         │
└─────────────────────────────────────────────┘
```

---

## Multi-Tenancy Model

**Tenant unit = Organization**

```
User  ──(many-to-many via organization_users)──  Organization
                                                      │
                                   ┌──────────────────┤
                                   ▼                  ▼
                                 Events            Account
                               Guests              (billing layer)
                              Payments
```

- Users belong to multiple organizations with roles: **Owner, Admin, Editor, Viewer**
- `users.current_organization_id` is the DB source of truth for active org
- `OrganizationContext` service manages switching — always call `OrganizationContext::current()`
- `EnsureOrganizationSelected` middleware enforces org context before tenant routes
- `SpatiePermissionTeam` middleware scopes Spatie permissions to the current organization

See [[Architecture/Services/OrganizationContext|OrganizationContext Service]]

---

## Domain Systems

### 1. Event Management
- Events have lifecycle: `Draft → PendingPayment → Active → Completed/Cancelled`
- Events belong to an Organization
- Each event has: Guests, Tables (seating layout), Invitations

### 2. Guest & Invitation System
- Guests are unique per event (not global users)
- Invitations link a Guest to an Event with a public `slug` token
- RSVP responses captured via web, WhatsApp, or voice call
- `RsvpResponseType`: Attending, Declining, Maybe

### 3. Seating System
- `EventTable` — named table with capacity
- `SeatAssignment` — links Guest to a Table
- Updated via `PUT /api/organizations/{org}/events/{event}/seat-assignments`

### 4. Billing System
- Event-level payments (one-time, per event)
- SUMIT gateway via redirect or PaymentsJS token
- Webhook-driven state transitions (webhook is source of truth)
- See [[Architecture/Services/BillingService|BillingService]]

### 5. Product Engine (Subscription/Entitlement)
- `Account` → `Products` → `AccountEntitlements` → feature flags & limits
- `FeatureResolver` resolves per-account feature values with cache (5 min TTL)
- `SubscriptionService` manages plan lifecycle + trials
- See [[Architecture/Services/FeatureResolver|FeatureResolver]]

### 6. Notifications
- WhatsApp: Twilio `whatsapp:` channel with RSVP link
- Voice: Twilio Programmable Voice → Node.js WebSocket relay → Gemini Live (Hebrew)
- SMS: Twilio Verify API for OTP
- See [[Architecture/Services/Notifications|Notifications & Voice RSVP]]

---

## System Admin (Superuser)

- `users.is_system_admin` flag — system-wide authority
- `users.is_disabled` — blocks login
- Routes: `/system/*` (bypass tenant middleware entirely)
- **Impersonation**: System admins can impersonate any organization (60 min expiry)
- All admin actions logged via `SystemAuditLogger`

---

## Authentication

- **Standard**: Email + password (Breeze)
- **Passkeys**: WebAuthn via `laragear/webauthn`
- Post-login redirect: System admins → `system.dashboard`, others → `dashboard`

---

## Authorization

- **Policies** (`app/Policies/`): `EventPolicy`, `OrganizationPolicy`, `GuestPolicy`, `PaymentPolicy`
- **Spatie Permissions**: Team-scoped per organization, synced via `PermissionSyncService`
- See [[Architecture/Permissions|Permissions System]]

---

## Directory Structure

```
app/
├── Console/Commands/      # Artisan commands (ProductEngine health, trial expiry)
├── Contracts/             # PaymentGatewayInterface, BillingProvider
├── Enums/                 # EventStatus, PaymentStatus, OrganizationUserRole, etc.
├── Events/                # ProductEngineEvent, RsvpReceived, Billing/*
├── Http/
│   ├── Controllers/Api/   # Tenant-scoped API controllers
│   ├── Controllers/Dashboard/ # Dashboard Livewire page controllers
│   ├── Controllers/System/   # Admin panel
│   ├── Controllers/Auth/     # Breeze auth
│   ├── Controllers/WebAuthn/ # Passkey controllers
│   ├── Controllers/Twilio/   # Voice RSVP + calling
│   ├── Middleware/            # EnsureOrganizationSelected, SpatiePermissionTeam, ImpersonationExpiry
│   └── Requests/              # FormRequest validation classes
├── Jobs/                  # SyncOrganizationSubscriptionsJob
├── Listeners/             # Billing audit, WebAuthn credential
├── Livewire/              # Interactive components (Billing, Dashboard, System, Profile)
├── Models/                # Eloquent models
├── Policies/              # Authorization policies
└── Services/              # Business logic services
```

---

## Related

- [[Architecture/APIs/REST-API|REST API Reference]]
- [[Architecture/Database/Schema|Database Schema]]
- [[Architecture/Services/BillingService|Billing Service]]
- [[Architecture/Services/FeatureResolver|Feature Resolver / Product Engine]]
- [[Architecture/Services/OrganizationContext|Organization Context]]
- [[Architecture/Services/Notifications|Notifications & Voice RSVP]]
- [[Architecture/Permissions|Permissions System]]
- [[Architecture/Infrastructure|Infrastructure & Deployment]]
