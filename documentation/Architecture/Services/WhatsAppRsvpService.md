---
date: 2026-03-16
tags: [architecture, service, whatsapp, twilio, notifications]
status: active
---

# WhatsAppRsvpService + CallingService

> Related: [[Architecture/Services/Notifications|Notifications]] · [[Architecture/Services/BillingService|BillingService]] · [[Architecture/AsyncQueue|Async Queue]]

Two services handle outbound guest communication via Twilio — WhatsApp messages and AI voice calls.

---

## WhatsAppRsvpService

`App\Services\WhatsAppRsvpService`

Sends WhatsApp messages with personalised RSVP links to guests via the Twilio Messaging API.

### Constructor

```php
public function __construct(private readonly TwilioClient $twilio)
```

### Methods

#### `sendRsvpLink(Invitation $invitation): array`

Main entry point. Sends a WhatsApp message to the guest linked to the invitation.

**Flow:**
1. Load guest + event via `loadMissing`
2. Validate guest exists and has a phone
3. Normalise phone to E.164 via `phoneToE164()`
4. Validate result matches `/^\+[1-9]\d{8,14}$/`
5. Build RSVP URL (`/rsvp/{slug}`)
6. Resolve `whatsappFrom()` — fails fast if not configured
7. Call `twilio->messages->create('whatsapp:{toE164}', [from, body])`
8. Poll delivery status once after 2s via `checkMessageDeliveryFailure()`

**Return shape:**
```php
['sid' => string, 'success' => bool, 'error?' => string]
```

---

#### `phoneToE164(string $phone): ?string` _(static)_

Normalises Israeli phone numbers to E.164 format.

| Input format | Output |
|---|---|
| `0501234567` (10 digits, starts `0`) | `+972501234567` |
| `501234567` (9 digits, starts `5`) | `+972501234567` |
| `972501234567` (12 digits, starts `972`) | `+972501234567` |
| International `+1234567890` | `+1234567890` |
| Invalid | `null` |

---

#### `rsvpUrl(Invitation $invitation): string`

Returns the public RSVP URL: `{APP_URL}/rsvp/{invitation->slug}`.

---

#### `whatsappFrom(): ?string` _(private)_

Resolves the sender address:
1. Prefer `TWILIO_WHATSAPP_FROM` env (Sandbox: `+14155238886`, Business: approved number)
2. Fall back to `TWILIO_NUMBER` (voice number, prefixed with `whatsapp:`)
3. Returns `null` if neither is set → `sendRsvpLink` returns error

---

### Delivery Verification

After sending, polls the message SID once (2s sleep) to detect immediate failure:

| Twilio Error Code | Hebrew message |
|---|---|
| `63015` | נמען לא הצטרף ל-WhatsApp Sandbox |
| `63016` | הודעה נחסמה על ידי הנמען |
| `63017` | מספר לא רשום ב-WhatsApp |
| `21211` | מספר הנמען לא תקין |

---

### Configuration

| Env Variable | Purpose |
|---|---|
| `TWILIO_WHATSAPP_FROM` | Sender number (Sandbox or Business) |
| `TWILIO_NUMBER` | Voice number, used as fallback WhatsApp sender |
| `TWILIO_ACCOUNT_SID` | Twilio credentials |
| `TWILIO_AUTH_TOKEN` | Twilio credentials |

---

## CallingService

`App\Services\CallingService`

Initiates outbound AI voice RSVP calls via Twilio. Checks feature entitlement and usage limits before placing a call.

### Constructor

```php
public function __construct(private readonly TwilioClient $twilio)
```

### Methods

#### `initiateCall(Guest $guest, Invitation $invitation): string`

Places a Twilio outbound call to the guest. Returns the call SID.

**Flow:**
1. `ensureAccountCanCall()` — feature + limit check
2. Normalise guest phone to E.164
3. Validate phone format
4. Build TwiML URL: `route('twilio.rsvp.connect', [guest_id, event_id, invitation_id])`
5. Build status callback: `route('twilio.calling.status', [invitation_id])`
6. `twilio->calls->create(toE164, TWILIO_NUMBER, [url, statusCallback, events])`

Status callback events: `initiated`, `ringing`, `answered`, `completed`.

---

#### `ensureAccountCanCall(Guest $guest): void` _(protected)_

Validates calling is permitted. Throws `RuntimeException` if:
- No billing account attached to organisation
- Feature `voice_rsvp_enabled` is disabled (`Feature::enabled()`)
- Monthly call usage ≥ `voice_rsvp_limit` (`Feature::integer()`)

Usage is checked via `account->featureUsage()` scoped to `voice_rsvp_calls` + current month period key (`Ym` format).

---

#### `findGuestByPhone(string $normalizedPhone): ?Guest`

Locates a guest by phone suffix (last 9 digits) across upcoming events (date ≥ today). Uses SQL `LIKE '%{suffix}'` — avoids PHP loops.

---

#### `normalizePhoneNumber(string $phone): string`

E.164 normalisation for Israeli numbers (same logic as `WhatsAppRsvpService::phoneToE164` but always returns a string).

---

### Twilio TwiML Flow

```
initiateCall()
    │
    └─► twilio.rsvp.connect (RsvpVoiceController)
            │  builds TwiML: <Say> or <Connect> to AI agent
            └─► twilio.calling.status (CallingController)
                    └─► updates Invitation.status, records usage
```

---

### Feature Flags Used

| Feature Key | Type | Purpose |
|---|---|---|
| `voice_rsvp_enabled` | boolean | Gates voice calling |
| `voice_rsvp_limit` | integer | Monthly call cap (null = unlimited) |

See [[Architecture/Services/FeatureResolver|FeatureResolver]] for resolution order.
