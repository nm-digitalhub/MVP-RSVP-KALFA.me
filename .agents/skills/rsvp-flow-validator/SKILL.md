---
name: rsvp-flow-validator
description: "Validates the end-to-end RSVP flow in this Laravel SaaS. Activates when debugging RSVP submission issues, tracing the voice RSVP call flow (Twilio → Node.js → Gemini Live → Laravel), verifying invitation slugs, checking RsvpResponse creation, debugging WhatsApp fallback, or testing the public /rsvp/{slug} route."
license: MIT
metadata:
  author: kalfa
---

# RSVP Flow Validator

## When to Apply

Activate this skill when:

- RSVP submissions are not being recorded
- Voice RSVP calls are failing or not triggering webhooks
- Invitation slugs are not resolving correctly
- WhatsApp fallback is not triggering after no-answer
- `POST /api/twilio/rsvp/process` is returning errors
- Debugging `save_rsvp` tool in Gemini Live session

## Architecture: Two RSVP Channels

### Channel 1: Web RSVP

```
Guest receives invitation URL
  → GET /rsvp/{slug}              (public, no auth)
  → Invitation resolved by slug
  → Guest submits form
  → POST /api/rsvp/{slug}
  → RsvpResponse created
  → Invitation status → Responded
```

### Channel 2: Voice RSVP (Twilio → Node.js → Gemini)

```
Outbound call initiated (Laravel)
  → Twilio dials guest
  → TwiML: <Connect><Stream url="wss://..."/> 
  → Node.js server.js (port 4000) receives media stream
  → Node relays audio ↔ Gemini Live BidiGenerateContent
  → Gemini calls save_rsvp tool with response data
  → Node POSTs to POST /api/twilio/rsvp/process
  → Laravel creates RsvpResponse
  → On no-answer / short call → WhatsApp fallback triggered
```

## Key Routes

| Method | URI | Description |
|---|---|---|
| GET | `/rsvp/{slug}` | Public RSVP page |
| POST | `/api/rsvp/{slug}` | Web RSVP submission |
| POST | `/api/twilio/rsvp/process` | Voice RSVP from Node.js |
| POST | `/api/twilio/voice/callback` | Twilio status callbacks |

## Validation Checklist

### 1. Invitation Slug Resolution

```php
// tinker
$inv = Invitation::where('slug', $slug)->with(['event', 'guest'])->first();
// Must exist, status must not be expired
echo $inv->status->value;  // Pending | Sent | Responded
```

### 2. RsvpResponse Creation

```php
// Expected after successful RSVP:
RsvpResponse::where('invitation_id', $inv->id)->latest()->first();
// response_type: Attending | Declining | Maybe
```

### 3. Voice Flow Debugging

```bash
# Check Node.js is running:
curl -s http://localhost:4000/health || echo "Node server down"

# Check env vars for Node:
node -e "console.log(process.env.GEMINI_API_KEY ? 'OK' : 'MISSING')"
node -e "console.log(process.env.PHP_WEBHOOK)"   # must = POST /api/twilio/rsvp/process URL
```

### 4. WhatsApp Fallback Trigger Conditions

Node.js triggers WhatsApp fallback when:
- Call status: `no-answer`, `busy`, `failed`
- Call duration < threshold (short call = no interaction)

### 5. TTS Voice (Hebrew)

```
Google he-IL-Standard-A + SSML
Language: Hebrew (RTL)
Engine: Gemini Live → text → Google TTS → Twilio
```

## Common Failure Points

| Symptom | Check |
|---|---|
| Slug 404 | Invitation `slug` column null or wrong route |
| Voice call connects but no audio | Node.js WebSocket port 4000 not reachable from Twilio |
| Gemini doesn't call save_rsvp | GEMINI_API_KEY missing or invalid |
| `/api/twilio/rsvp/process` 401 | Node not sending correct auth header / CALL_LOG_SECRET mismatch |
| WhatsApp not sent | TWILIO_WHATSAPP_FROM env missing or Twilio sandbox not approved |
| RsvpResponse duplicated | No idempotency check on process endpoint |

## Key Files

| File | Purpose |
|---|---|
| `server.js` | Node.js WebSocket relay (Twilio ↔ Gemini Live) |
| `routes/api.php` | `/api/rsvp/{slug}`, `/api/twilio/rsvp/process` |
| `app/Models/Invitation.php` | Slug, status, guest/event relations |
| `app/Models/RsvpResponse.php` | Response type enum, invitation FK |
| `app/Enums/RsvpResponseType.php` | Attending, Declining, Maybe |
| `app/Enums/InvitationStatus.php` | Pending, Sent, Responded |
| `resources/views/rsvp/` | Public RSVP form views |

## End-to-End Test

```php
// Feature test skeleton:
$invitation = Invitation::factory()->create(['status' => InvitationStatus::Sent]);

$response = $this->postJson("/api/rsvp/{$invitation->slug}", [
    'response_type' => RsvpResponseType::Attending->value,
    'guest_count' => 2,
]);

$response->assertOk();
$this->assertDatabaseHas('rsvp_responses', [
    'invitation_id' => $invitation->id,
    'response_type' => RsvpResponseType::Attending->value,
]);
$this->assertEquals(
    InvitationStatus::Responded,
    $invitation->fresh()->status
);
```
