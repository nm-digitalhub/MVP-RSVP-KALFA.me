---
date: 2026-03-16
tags: [architecture, events, lifecycle, state-machine]
status: active
---

# Event Lifecycle

## Overview

An **Event** in KALFA passes through a defined state machine from creation to archival.  
Related state machines govern **Invitation**, **Payment**, and **RsvpResponse** objects.

---

## Event Status Machine

```
Draft ──[checkout initiated]──► PendingPayment ──[webhook: succeeded]──► Active
  │                                   │                                      │
  │ (free event)                       └──[webhook: failed]──► Cancelled      │
  └─────────────────────────────────────────────────────────────────────────► Active
                                                                               │
                                                                ┌──────────────┤
                                                    [manual lock]│             │[archive]
                                                                 ▼             ▼
                                                              Locked        Archived
```

| Status | Description | Transitions |
|--------|-------------|-------------|
| `Draft` | Created, not published. Editable. | → `PendingPayment`, → `Active` (free) |
| `PendingPayment` | Checkout initiated. Awaiting webhook. | → `Active`, → `Cancelled` |
| `Active` | Published, accepting RSVPs and guests. | → `Locked`, → `Archived` |
| `Locked` | Frozen — no new RSVPs. Seating final. | → `Active`, → `Archived` |
| `Archived` | Completed. Read-only. | (terminal) |
| `Cancelled` | Payment failed or manually cancelled. | (terminal) |

> **Source:** `App\Enums\EventStatus`

---

## Invitation Status Machine

```
[Event goes Active]
        │
        ▼
    Pending ──[send notification]──► Sent ──[guest opens link]──► Opened
                                                                      │
                                              ┌───────────────────────┤
                                    [response submitted]         [time passes]
                                              ▼                       ▼
                                          Responded               Expired
```

| Status | Description |
|--------|-------------|
| `Pending` | Created, not yet sent to guest |
| `Sent` | WhatsApp/SMS/email dispatched |
| `Opened` | Guest opened the RSVP link |
| `Responded` | Guest submitted an RsvpResponse |
| `Expired` | Past event date, no response |

> **Source:** `App\Enums\InvitationStatus`

---

## RsvpResponse Types

| Type | Meaning |
|------|---------|
| `Attending` | Guest confirms attendance (+ guest count) |
| `Declining` | Guest declines |
| `Maybe` | Guest tentatively accepts |

> **Source:** `App\Enums\RsvpResponseType`

---

## Payment Status Machine

```
Pending ──[chargeWithToken() or redirect]──► Processing ──[webhook: success]──► Succeeded
                                                  │                                  │
                                        [webhook: fail]                       [refund issued]
                                                  ▼                                  ▼
                                               Failed                            Refunded

Pending / Processing ──[manual cancel]──► Cancelled
```

| Status | Description |
|--------|-------------|
| `Pending` | Payment record created |
| `Processing` | Token charged, awaiting SUMIT webhook |
| `Succeeded` | Webhook confirmed success — **only** this sets Event Active |
| `Failed` | Webhook reported failure |
| `Refunded` | Succeeded then refunded |
| `Cancelled` | Manually cancelled before processing |

> ⚠️ **Critical rule:** `initiateEventPaymentWithToken()` sets status to `Processing`, NOT `Succeeded`. Only the SUMIT webhook can set `Succeeded`.

> **Source:** `App\Enums\PaymentStatus`

---

## EventBilling Status

| Status | Meaning |
|--------|---------|
| `Pending` | Billing record created, payment outstanding |
| `Paid` | Payment succeeded (webhook confirmed) |
| `Cancelled` | Billing cancelled |

> **Source:** `App\Enums\EventBillingStatus`

---

## Full Lifecycle: Event + Billing + Payment

```
Organizer creates Event (Draft)
    │
    ├── [Free event] ──────────────────────────────────────────► Event: Active
    │
    └── [Paid event]
            │
            ▼
    initiate checkout
            │
            ├── Create EventBilling (Pending)
            ├── Create Payment (Pending)
            └── Event: Draft → PendingPayment
                    │
                    ├── Redirect flow: SUMIT hosted page
                    └── Token flow: chargeWithToken()
                                │
                                ▼
                        Payment: Processing
                                │
                    ────────────┼──────────────
                    SUMIT Webhook arrives
                    ────────────┼──────────────
                        ┌───────┴───────┐
                        ▼               ▼
                    Success           Failure
                        │               │
              Payment: Succeeded   Payment: Failed
              EventBilling: Paid   Event: stays PendingPayment
              Event: Active        (retry possible)
```

---

## Broadcasting

When a guest submits an RSVP, `RsvpReceived` event is dispatched and **broadcast** to the organizer's dashboard in real time:

```php
// App\Events\RsvpReceived implements ShouldBroadcast
broadcastOn() → PrivateChannel('event.{event_id}')
```

| Config | Value |
|--------|-------|
| Driver | `BROADCAST_CONNECTION` (default: `null` in dev, `reverb` in prod) |
| Channel | `private-event.{event_id}` |
| Auth | Sanctum / session |

---

## Related

- [[Architecture/Services/BillingService]] — Payment orchestration
- [[Architecture/Services/Notifications]] — Invitation delivery
- [[Architecture/APIs/REST-API]] — RSVP submission endpoints
- [[Architecture/Diagrams/03-Billing-Payment-Flow]] — Visual billing state machine
- [[Architecture/Diagrams/04-RSVP-Flow]] — Visual RSVP channel flow
- [[Architecture/Diagrams/05-Event-Lifecycle]] — Visual state machine diagram
