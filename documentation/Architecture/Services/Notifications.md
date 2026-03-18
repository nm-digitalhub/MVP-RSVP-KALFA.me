---
date: 2026-03-16
tags: [architecture, service, notifications, twilio, whatsapp, voice, rsvp]
status: active
---

# Notifications & Voice RSVP

## Overview

KALFA sends RSVP invitations via three channels: **WhatsApp**, **Voice Call** (AI-powered), and **SMS**. All use Twilio infrastructure.

---

## Channel 1: WhatsApp RSVP

**File**: `app/Services/WhatsAppRsvpService.php`

### Flow

```
Organizer clicks "Send Invitations"
        │
        ▼
InvitationController::send()
        │
        ▼
WhatsAppRsvpService::sendRsvpLink(invitation)
        │
        ├── Normalizes phone to E.164: 0501234567 → +972501234567
        ├── Builds RSVP URL: {app.url}/rsvp/{invitation.slug}
        └── Sends via Twilio: whatsapp:{phone}
```

### Phone Normalization Rules (Israeli numbers)

| Input format | Output |
|---|---|
| `0501234567` (10 digits, starts with 0) | `+972501234567` |
| `501234567` (9 digits, starts with 5) | `+972501234567` |
| `972501234567` (11-12 digits, starts with 972) | `+972501234567` |
| International E.164 | Pass-through with `+` prefix |

---

## Channel 2: AI Voice RSVP

### Architecture

```
CallingService::initiateCall(guest)
        │
        ▼
Twilio Programmable Voice — calls guest's phone
        │
        ▼
TwiML: <Connect><Stream url="wss://…/twilio/rsvp/connect"/>
        │
        ▼
Node.js server.js (WebSocket relay)
        │
        ├── Receives Twilio Media Stream (mulaw audio)
        ├── Relays bidirectionally to Gemini Live BidiGenerateContent
        ├── TTS: Google Hebrew he-IL-Standard-A + SSML
        └── On RSVP captured: calls Laravel POST /api/twilio/rsvp/process
```

### Node.js Environment Variables

| Var | Purpose |
|-----|---------|
| `GEMINI_API_KEY` | Google Gemini Live API key |
| `PHP_WEBHOOK` | Laravel endpoint to POST RSVP result |
| `CALL_LOG_URL` | Laravel log append endpoint |
| `CALL_LOG_SECRET` | Shared secret for log endpoint auth |

### Laravel Voice Controllers

| Controller | Route | Purpose |
|---|---|---|
| `CallingController::call()` | `POST /twilio/calling/initiate` | Initiate outbound call |
| `CallingController::statusCallback()` | `POST /twilio/calling/status` | Twilio call status webhook |
| `RsvpVoiceController::connect()` | `GET|POST /twilio/rsvp/connect` | TwiML response for Twilio Stream |
| `RsvpVoiceController::process()` | `POST /api/twilio/rsvp/process` | Node.js posts RSVP result here |

### WhatsApp Fallback

If call not answered or call duration is short, Node.js triggers WhatsApp message with RSVP link as fallback.

---

## Channel 3: SMS OTP (Twilio Verify)

- **Service SID**: `VA5f1c126dd6b47bcd05492197c1c36f73`
- Used for phone number verification flows
- `VerifyWhatsAppService` handles verification token sending/checking

---

## Guest Phone Lookup

`CallingService::findGuestByPhone()` uses a SQL suffix match (last 9 digits) to find guests across upcoming events:

```php
Guest::whereHas('event', fn($q) => $q->where('event_date', '>=', now()->startOfDay()))
    ->where('phone', 'like', '%' . $phoneSuffix)
    ->first();
```

---

## Invitation Token / Slug

Every `Invitation` has:
- `token` — 32-char random (secure operations, not exposed in public URLs)
- `slug` — 10-char random (public RSVP URL: `{app.url}/rsvp/{slug}`)

### RSVP Public API Flow

```
GET /api/rsvp/{slug}            ← throttle:rsvp_show (60 req/min)
    │
    ├── Invitation WHERE slug = ? → 404 if not found
    ├── event.status != Active   → 404 ("Event not available")
    └── Returns: event_name, event_date, venue_name, guest_name
        (no PII beyond guest name — no email, no phone)
```

```
POST /api/rsvp/{slug}/responses  ← throttle:rsvp_submit (10 req/min)
    │
    ├── Invitation WHERE slug = ? → 404
    ├── event.status != Active   → 404
    │
    └── DB::transaction:
        ├── RsvpResponse::updateOrCreate(         ← idempotent
        │       {invitation_id, guest_id},
        │       {type, guest_count, ip, user_agent}
        │   )
        └── invitation.status = Responded
            invitation.responded_at = now()
```

### Security Properties

| Property | Implementation |
|----------|---------------|
| **Unauthenticated access** | RSVP endpoints require no auth — public by design |
| **No enumeration** | Slugs are 10-char random — not sequential, not guessable |
| **Event status guard** | Non-Active events return 404 (prevents responding to closed events) |
| **Idempotent responses** | Re-submitting updates the same record — no duplicate RSVPs |
| **IP + User-Agent recorded** | Every submission stores `ip` and `user_agent` for audit |
| **Rate limiting (show)** | `throttle:rsvp_show` — 60 req/min globally |
| **Rate limiting (submit)** | `throttle:rsvp_submit` — 10 req/min globally |
| **No card/PCI data** | RSVP endpoints never handle payment information |

### Anti-Abuse Considerations

The current rate limiter is global (not per-IP). For stricter abuse protection, consider:
- `Limit::perMinute(10)->by($request->ip())` — per-IP rate limiting
- CAPTCHA on the frontend RSVP form for first-time responses
- Honeypot fields on the submission form

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/APIs/REST-API|REST API — Public RSVP Endpoints]]
- `app/Services/CallingService.php`
- `app/Services/WhatsAppRsvpService.php`
- `app/Services/VerifyWhatsAppService.php`
- `server.js` (Node.js Gemini Live relay)

---

## Related

- [[Architecture/EventLifecycle]] — Invitation status machine (Pending→Sent→Opened→Responded)
- [[Architecture/AsyncQueue]] — RsvpReceived broadcast event
- [[Architecture/Diagrams/04-RSVP-Flow]] — Visual RSVP channel diagram
- [[Architecture/Glossary]] — WhatsApp RSVP, Voice RSVP, CallingService definitions
