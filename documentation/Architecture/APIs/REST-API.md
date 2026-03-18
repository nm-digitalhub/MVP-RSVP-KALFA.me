---
date: 2026-03-16
tags: [architecture, api, rest, endpoints]
status: active
---

# REST API Reference

**Base URL**: `/api`  
**Auth**: `auth:sanctum` (Bearer token) on all tenant routes  
**Tenant scoping**: All tenant routes include `{organization}` in path

---

## Organization

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | `/organizations/{organization}` | `organizations.show` | Get organization details |
| PATCH | `/organizations/{organization}` | `organizations.update` | Update organization |

---

## Events

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | `/organizations/{org}/events` | `events.index` | List events |
| POST | `/organizations/{org}/events` | `events.store` | Create event |
| GET | `/organizations/{org}/events/{event}` | `events.show` | Get event |
| PUT/PATCH | `/organizations/{org}/events/{event}` | `events.update` | Update event |
| DELETE | `/organizations/{org}/events/{event}` | `events.destroy` | Delete event |

**Status lifecycle**: `Draft → PendingPayment → Active → Completed/Cancelled`

---

## Guests

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | `/organizations/{org}/events/{event}/guests` | `guests.index` | List guests |
| POST | `/organizations/{org}/events/{event}/guests` | `guests.store` | Add guest |
| GET | `.../guests/{guest}` | `guests.show` | Get guest |
| PUT/PATCH | `.../guests/{guest}` | `guests.update` | Update guest |
| DELETE | `.../guests/{guest}` | `guests.destroy` | Remove guest |
| POST | `.../guests/import` | `guests.import` | Bulk import guests |

---

## Tables (Seating Layout)

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | `.../event-tables` | `event-tables.index` | List tables |
| POST | `.../event-tables` | `event-tables.store` | Create table |
| GET | `.../event-tables/{eventTable}` | `event-tables.show` | Get table |
| PUT/PATCH | `.../event-tables/{eventTable}` | `event-tables.update` | Update table |
| DELETE | `.../event-tables/{eventTable}` | `event-tables.destroy` | Delete table |

---

## Seat Assignments

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | `.../seat-assignments` | `seat-assignments.index` | Get all assignments |
| PUT | `.../seat-assignments` | `seat-assignments.update` | Bulk update assignments |

---

## Invitations

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | `.../invitations` | `invitations.index` | List invitations |
| POST | `.../invitations` | `invitations.store` | Create invitation |
| POST | `.../invitations/{invitation}/send` | `invitations.send` | Send via WhatsApp/SMS |

---

## Checkout (Event Payments)

| Method | Path | Name | Description |
|--------|------|------|-------------|
| POST | `/organizations/{org}/events/{event}/checkout` | `checkout.initiate` | Initiate event payment |
| GET | `/payments/{payment}` | `payments.show` | Get payment status |

### Checkout Request

The `InitiateCheckoutRequest` validates the checkout payload.  
⚠️ **Card data is strictly forbidden in the payload — only single-use tokens.**

---

## Public RSVP (No Auth)

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | `/rsvp/{slug}` | `api.rsvp.show` | Get invitation by slug |
| POST | `/rsvp/{slug}/responses` | `api.rsvp.responses.store` | Submit RSVP response |

Rate limited:
- `throttle:rsvp_show` on GET
- `throttle:rsvp_submit` on POST

### RSVP Response Types

`RsvpResponseType` enum: `Attending`, `Declining`, `Maybe`

---

## Payment Webhooks

| Method | Path | Name | Description |
|--------|------|------|-------------|
| POST | `/webhooks/{gateway}` | `webhooks.handle` | Payment gateway webhook |

- Throttled: `throttle:webhooks`
- CSRF exempt
- `{gateway}`: `sumit`, `stub`

---

## Twilio / Voice (Internal)

| Method | Path | Name | Description |
|--------|------|------|-------------|
| POST | `/api/twilio/rsvp/process` | `api.twilio.rsvp.process` | Node.js posts RSVP result |
| POST | `/api/twilio/calling/log` | `api.twilio.calling.log.append` | Append call log from Node.js |

---

## Web Routes (Key)

| Path | Description |
|------|-------------|
| `/` | Redirect: dashboard (auth) or login |
| `/dashboard` | Livewire dashboard |
| `/organizations` | Org selection |
| `/checkout/{org}/{event}` | PaymentsJS tokenization page |
| `/checkout/status/{payment}` | Payment result page |
| `/event/{slug}` | Public event page |
| `/rsvp/{slug}` | Public RSVP form |
| `/system/dashboard` | System admin panel |
| `/twilio/calling/initiate` | Initiate outbound RSVP call |

---

## Auth Routes

| Path | Description |
|------|-------------|
| `POST /webauthn/login/options` | Passkey assertion options |
| `POST /webauthn/login` | Passkey login |
| `POST /webauthn/register/options` | Passkey registration options |
| `POST /webauthn/register` | Register passkey |

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Services/BillingService|BillingService]]
- [[Architecture/Services/Notifications|Notifications & Voice RSVP]]
- `routes/api.php`
- `routes/web.php`
