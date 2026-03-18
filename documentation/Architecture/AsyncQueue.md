---
date: 2026-03-16
tags: [architecture, queue, async, jobs, events, broadcasting]
status: active
---

# Async Processing — Queue, Jobs & Events

## Overview

KALFA uses Laravel's queue system for deferred work and an event/listener system for decoupled side effects.  
Real-time updates to the frontend are delivered via Laravel Broadcasting (Reverb/Pusher).

---

## Queue Configuration

| Setting | Value |
|---------|-------|
| Default driver | `database` (via `QUEUE_CONNECTION` env) |
| Queue table | `jobs` |
| Failed jobs table | `failed_jobs` |
| Batching table | `job_batches` |
| Retry driver | `database-uuids` |

> For production, Redis is available as an alternative connection (`redis` driver configured).

---

## Jobs

### `SyncOrganizationSubscriptionsJob`

| Property | Value |
|----------|-------|
| File | `app/Jobs/SyncOrganizationSubscriptionsJob.php` |
| Interface | `ShouldQueue` |
| Tries | 3 |
| Backoff | 30s → 120s → 300s (exponential) |
| Purpose | Sync org subscriptions from SUMIT API, bust cache |

**Flow:**
```
Dispatch(organization, actorId)
    │
    ▼
SystemBillingService::syncOrganizationSubscriptions()
    │
    ▼
SystemBillingService::forgetSubscriptionCache()
    │
    ▼
SystemAuditLogger::log('organization.subscriptions_synced')
```

**Retry Strategy:**  
Transient SUMIT API failures are retried automatically up to 3 times with exponential backoff.  
After 3 failures, job moves to `failed_jobs`.

---

## Laravel Events & Listeners

### Event → Listener Mapping

| Event | Listener | Side Effect |
|-------|----------|-------------|
| `RsvpReceived` | *(broadcast only)* | Real-time push to `private-event.{id}` channel |
| `ProductEngineEvent` | `LogProductEngineEvent` | Structured log entry for product engine changes |
| `Billing\SubscriptionCancelled` | `Billing\AuditBillingEvent` | Audit trail entry in `system_audit_logs` |
| `Billing\TrialExtended` | `Billing\AuditBillingEvent` | Audit trail entry in `system_audit_logs` |
| *(WebAuthn credential)* | `StoreWebAuthnCredentialInSession` | Stores passkey cred in session after register |

### `RsvpReceived` (Broadcast)

```php
// Implements ShouldBroadcast
broadcastOn() → PrivateChannel('event.' . invitation->event_id)
```

- Dispatched when a guest submits an RSVP response
- Frontend dashboard listens on `private-event.{id}` 
- Allows organizer to see RSVP counts update live without polling

### `ProductEngineEvent`

Dispatched when feature resolution decisions change (plan changes, entitlement updates).  
`LogProductEngineEvent` listener writes a structured log entry for observability.

### `Billing\SubscriptionCancelled` / `Billing\TrialExtended`

Dispatched by `SubscriptionService` / `SubscriptionManager` when billing state changes.  
`AuditBillingEvent` listener records to the audit trail with actor, org, and metadata.

---

## Broadcasting Architecture

```
Guest submits RSVP
        │
        ▼
RsvpController → RsvpResponse::create()
        │
        ▼
RsvpReceived::dispatch($invitation)
        │
        ▼
Laravel Broadcasting ──► Reverb (WebSocket server)
                                │
                         PrivateChannel('event.{id}')
                                │
                         Organizer dashboard (Livewire)
                         updates guest count in real time
```

| Config | Description |
|--------|-------------|
| `BROADCAST_CONNECTION` | `null` (dev) / `reverb` or `pusher` (prod) |
| Auth | Sanctum / session-based channel auth |
| Channel prefix | `private-` (authenticated) |

---

## Async Processing Flow (Full)

```
HTTP Request (trigger)
        │
        ├─ Dispatch Event ──► Listener (sync, same request)
        │                                    │
        │                           [AuditBillingEvent]
        │                           [LogProductEngineEvent]
        │
        └─ Dispatch Job ──► Queue (database driver)
                                    │
                          [queue:work] daemon picks up
                                    │
                          [SyncOrganizationSubscriptionsJob]
                                    │
                           ┌────────┴─────────┐
                         OK (done)         FAIL (retry up to 3×)
                                                │
                                        [failed_jobs table]
                                        Alert / manual requeue
```

---

## Monitoring & Observability

| Tool | Purpose |
|------|---------|
| `SystemAuditLogger` | Structured audit trail for billing, org, and user events |
| `LogProductEngineEvent` | Logs feature resolution changes |
| `ProductEngineOperationsMonitor` | Health checks for product engine |
| Failed jobs table | Dead-letter queue — manual inspection |
| `RequestId` middleware | Unique `X-Request-Id` header on all requests for log correlation |

---

## Related

- [[Architecture/Services/BillingService]] — Dispatches subscription jobs
- [[Architecture/Services/FeatureResolver]] — Triggers ProductEngineEvent
- [[Architecture/Services/Notifications]] — WhatsApp/SMS dispatch (may be async)
- [[Architecture/EventLifecycle]] — Event/Invitation state machines
- [[Architecture/Diagrams/07-Async-Queue]] — Visual queue/event flow
