---
date: 2026-03-16
tags: [architecture, glossary, terminology]
status: active
---

# Glossary — KALFA Platform Terms

Canonical definitions for all domain terms used across the KALFA codebase and documentation.  
When in doubt, use these names exactly as they appear below.

---

## Core Domain

| Term | Definition |
|------|-----------|
| **Organization** | The multi-tenant root entity. Represents a business or event organizer. Users belong to organizations via `organization_users`. The `current_organization_id` on `users` determines the active tenant context. |
| **Account** | The billing entity attached to an Organization. Holds subscription, plan, and product entitlements. One Organization → One Account. |
| **User** | An authenticated person. May belong to multiple Organizations with different roles. `current_organization_id` is the DB source of truth for active org. |
| **OrganizationUser** | The pivot record linking User ↔ Organization. Carries the `role` (Owner / Admin / Editor / Viewer). |
| **Event** | A single hosted event (wedding, birthday, conference). Belongs to an Organization. Has guests, tables, invitations, and billing. |
| **Guest** | A person invited to an Event. May have phone, email. Linked to Invitations. |
| **Invitation** | A specific invite sent to a Guest for an Event. Carries `status` and links to an `RsvpResponse`. |
| **RsvpResponse** | The guest's answer to an invitation: Attending / Declining / Maybe, plus guest count. |
| **EventTable** | A seating table at an Event. Has capacity. Guests are assigned via `SeatAssignment`. |
| **SeatAssignment** | Links a Guest (or Invitation) to an EventTable. |

---

## Billing & Subscriptions

| Term | Definition |
|------|-----------|
| **Plan** | A named tier (e.g., Basic, Pro, Enterprise). Belongs to a Product. |
| **Product** | A sellable package. Has Plans, Entitlements, and Limits. |
| **AccountProduct** | An org's active Product subscription. |
| **AccountSubscription** | A recurring subscription record. Linked to AccountProduct. |
| **AccountEntitlement** | A specific feature unlocked for an Account. May be boolean or metered. |
| **AccountFeatureUsage** | Usage counter for metered entitlements. |
| **ProductEntitlement** | Default entitlement included with a Product. |
| **ProductFeature** | A named feature that can be gated (e.g., `whatsapp_rsvp`, `voice_rsvp`). |
| **ProductLimit** | A numeric limit on a feature (e.g., max guests per event). |
| **Entitlement Type** | `boolean` (on/off) or `metered` (usage-based). Source: `EntitlementType` enum. |
| **BillingIntent** | A record of an initiated checkout (before Payment). |
| **EventBilling** | Billing record for a specific Event. Status: Pending / Paid / Cancelled. |
| **Payment** | A payment transaction record. Status: Pending → Processing → Succeeded / Failed / Refunded / Cancelled. |
| **BillingWebhookEvent** | A raw webhook payload received from SUMIT, stored for idempotency. |
| **SUMIT** | The Israeli payment gateway provider used by KALFA (also referenced as OfficeGuy in older code). |

---

## Product Engine

| Term | Definition |
|------|-----------|
| **FeatureResolver** | The service that determines if a feature is available for an account. Uses a 5-level priority chain. |
| **Feature Key** | A string identifier for a feature (e.g., `whatsapp_rsvp`). Used as cache key and lookup. |
| **Priority Chain** | The 5-level resolution order: Override → AccountEntitlement → ProductEntitlement → Plan → Default. |
| **Cache Key** | `feature:{accountId}:{featureKey}` — 5-minute TTL via `Cache::memo()`. |

---

## Multi-Tenancy

| Term | Definition |
|------|-----------|
| **Tenant** | Synonym for Organization in multi-tenancy context. |
| **OrganizationContext** | The Laravel service (`App\Services\OrganizationContext`) that provides the current org. Always use `OrganizationContext::current()` — never read org from request directly. |
| **Impersonation** | System admin acting as an org member for support. Managed by `ImpersonationExpiry` middleware. |
| **Team** (Spatie) | In Spatie permissions, "team" = Organization. Team ID = `current_organization_id`. |

---

## Notifications & Integrations

| Term | Definition |
|------|-----------|
| **WhatsApp RSVP** | Sending RSVP links to guests via WhatsApp Meta Business API. Service: `WhatsAppRsvpService`. |
| **Voice RSVP** | AI-powered voice call that collects RSVP response. Twilio → Node.js relay → Gemini Live API. |
| **CallingService** | Orchestrates voice RSVP calls — finds guest, ensures invitation, initiates Twilio call. |
| **Gemini Live** | Google's real-time AI voice API used for Hebrew TTS and RSVP conversation. |
| **Reverb** | Laravel Reverb — WebSocket server for real-time broadcasting (alternative: Pusher). |

---

## Auth & Security

| Term | Definition |
|------|-----------|
| **Passkey** | A WebAuthn/FIDO2 passwordless credential bound to a device. Managed in `ManagePasskeys` Livewire component. |
| **Sanctum** | Laravel Sanctum — API token authentication for REST API clients. |
| **RequestId** | A unique UUID added to every HTTP request as `X-Request-Id` header. Used for log correlation. |
| **SystemAdmin** | A superuser role with access to all organizations. Must impersonate before accessing tenant data. |

---

## Status Enums

| Enum | Values |
|------|--------|
| `EventStatus` | `Draft` · `PendingPayment` · `Active` · `Locked` · `Archived` · `Cancelled` |
| `InvitationStatus` | `Pending` · `Sent` · `Opened` · `Responded` · `Expired` |
| `PaymentStatus` | `Pending` · `Processing` · `Succeeded` · `Failed` · `Refunded` · `Cancelled` |
| `EventBillingStatus` | `Pending` · `Paid` · `Cancelled` |
| `RsvpResponseType` | `Attending` · `Declining` · `Maybe` |
| `OrganizationUserRole` | `Owner` · `Admin` · `Editor` · `Viewer` |
| `AccountSubscriptionStatus` | `Trial` · `Active` · `PastDue` · `Cancelled` |
| `AccountProductStatus` | `Active` · `Suspended` · `Revoked` |
| `ProductStatus` | `Draft` · `Active` · `Archived` |
| `EntitlementType` | `Boolean` (on/off feature flag) · `Number` (numeric limit) · `Text` (string value) |
| `UsagePolicyDecision` | `Allowed` · `AllowedWithOverage` · `Blocked` |
| `ProductPriceBillingCycle` | `Monthly` · `Yearly` · `Usage` (pay-per-use / overage) |

---

## Billing & Payment Terms

| Term | Definition |
|------|-----------|
| **SUMIT** | Israeli payment gateway (OfficeGuy) used for event billing and subscriptions. Accessed via `SumitPaymentGateway` and `SumitBillingProvider`. |
| **OfficeGuy** | The vendor package (`officeguy/laravel-sumit-gateway`) wrapping SUMIT APIs. |
| **EventBilling** | A billing record for a single event — created when event moves to `PendingPayment`. |
| **AccountSubscription** | The platform's Product Engine subscription record. Separate from OfficeGuy subscription. |
| **Payable** | Interface (`App\Contracts\BillingProvider`) implemented by `EventBillingPayable`, `SumitUsageChargePayable` etc. to unify billing API calls. |
| **Overage** | Usage beyond the plan's included limit, billed per-unit via `UsageMeter::billOverageIfRequired`. |
| **UsageRecord** | DB record of a metered usage event (e.g. `voice_rsvp_calls`). Stored in `usage_records`. |
| **Webhook** | HTTP callback from SUMIT (payment result) or Twilio (call/message status). Verified by HMAC. See [[Architecture/Services/BillingService|BillingService]]. |

---

## Technical Infrastructure Terms

| Term | Definition |
|------|-----------|
| **Cache::memo()** | In-process per-request memoisation layer on top of Redis. Prevents duplicate DB/Redis reads within one request. |
| **ProductEngineEvent** | Laravel event dispatched on every Product Engine state change. Consumed by `LogProductEngineEvent` listener. |
| **SystemAuditLog** | DB record of a privileged admin action. Written by [[Architecture/Services/SystemAuditLogger|SystemAuditLogger]]. |
| **RequestId** | UUID header (`X-Request-Id`) added to every HTTP request for log correlation. |
| **Reverb** | Laravel Reverb — self-hosted WebSocket server for broadcasting real-time events to the frontend. |
| **TwiML** | Twilio Markup Language — XML returned by `RsvpVoiceController` to instruct Twilio on call handling. |

---

## Related

- [[Architecture/Overview]] — Full system architecture
- [[Architecture/Services/OrganizationContext]] — Multi-tenancy implementation
- [[Architecture/Services/BillingService]] — Billing implementation
- [[Architecture/Services/SubscriptionService]] — Subscription lifecycle
- [[Architecture/Services/SubscriptionManager]] — Application-layer subscription facade
- [[Architecture/Services/FeatureResolver]] — Product engine
- [[Architecture/Services/UsagePolicyService]] — Hard/soft usage enforcement
- [[Architecture/Services/UsageMeter]] — Usage recording and overage billing
- [[Architecture/Services/PermissionSyncService]] — Spatie permissions sync
- [[Architecture/Services/OrganizationMemberService]] — Invite and member management
- [[Architecture/Services/WhatsAppRsvpService]] — WhatsApp delivery, voice RSVP
- [[Architecture/Services/SystemAuditLogger]] — Audit trail
- [[Architecture/Services/SystemBillingService]] — OfficeGuy/SUMIT legacy billing
- [[Architecture/Services/EventLinks]] — Calendar and navigation links
- [[Architecture/Services/ProductEngineMonitor]] — Scheduler health and integrity
- [[Architecture/Caching]] — Cache layers and invalidation
- [[Architecture/Permissions]] — Roles and permissions
- [[Architecture/EventLifecycle]] — State machine definitions
