# Twilio Voice Integration Architecture

**Last Updated:** 2026-03-23
**Component:** Voice-based RSVP capture using AI

---

## Overview

A unique voice-based RSVP system that uses Twilio Programmable Voice, Node.js WebSocket relay, and Google Gemini Live API to enable phone-based RSVP capture with Hebrew text-to-speech.

---

## Architecture Flow

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│   Twilio    │──────│  Node.js     │──────│  Gemini     │
│  (TwiML)    │ WS   │ (server.js)  │ API   │  Live AI    │
└─────────────┘      └──────────────┘      └─────────────┘
      │                     │                       │
      │                     │                       │
      ▼                     ▼                       ▼
  Media Stream        WebSocket Bridge         BidiGenerateContent
 (audio in/out)      (relay & tool call)       (voice-to-voice)
                                               │
                                               ▼
                                         Tool: save_rsvp
                                               │
                                               ▼
                                  POST /api/twilio/rsvp/process
```

---

## Components

### 1. Twilio Setup

**TwiML Configuration:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Connect>
        <Stream url="wss://your-server.com/twilio/media" />
    </Connect>
</Response>
```

**Voice Configuration:**
- **TTS Engine**: Google.he-IL-Standard-A (Hebrew)
- **SSML Support**: Yes, for enhanced TTS control
- **Fallback**: WhatsApp message on no-answer or short call (< 10 seconds)

### 2. Node.js WebSocket Relay (`server.js`)

**Location:** Root project directory

**Environment Variables:**
| Variable | Purpose |
|----------|---------|
| `GEMINI_API_KEY` | Google Gemini Live API key |
| `PHP_WEBHOOK` | Laravel webhook URL for RSVP processing |
| `CALL_LOG_URL` | Call logging service endpoint |
| `CALL_LOG_SECRET` | Webhook secret for authentication |

**Responsibilities:**
1. Accept WebSocket connection from Twilio Media Stream
2. Relay audio data to Gemini Live API
3. Handle tool calls from Gemini (`save_rsvp`)
4. POST processed RSVP data to Laravel backend
5. Stream TTS audio back to caller

### 3. Laravel Backend Processing

**Route:** `POST /api/twilio/rsvp/process`

**Controller:** `app/Http/Controllers/Twilio/RsvpVoiceController.php`

**Flow:**
1. Receive RSVP data (guest_id, event_id, response_type, message, etc.)
2. Validate guest and event
3. Create or update `RsvpResponse` record
4. Trigger any follow-up actions (WhatsApp confirmation, email, etc.)

---

## Database Schema

### Related Tables

| Table | Purpose |
|-------|---------|
| `invitations` | RSVP tokens/slugs |
| `guests` | Guest information |
| `rsvp_responses` | Captured RSVP responses |

### RSVP Response Model

```php
// app/Models/RsvpResponse.php
class RsvpResponse extends Model
{
    protected $fillable = [
        'invitation_id',
        'guest_id',
        'response',  // attending, declining, maybe
        'attendees_count',
        'message',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'response' => RsvpResponseType::class,
    ];

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
```

---

## Controllers

### RsvpVoiceController

**Location:** `app/Http/Controllers/Twilio/RsvpVoiceController.php`

```php
class RsvpVoiceController extends Controller
{
    public function process(Request $request)
    {
        // Validate request from Node.js relay
        $validated = $request->validate([
            'invitation_slug' => 'required|string',
            'response' => 'required|in:attending,declining,maybe',
            'message' => 'nullable|string',
            'attendees_count' => 'nullable|integer|min:1|max:20',
        ]);

        // Find invitation by slug
        $invitation = Invitation::where('slug', $validated['invitation_slug'])
            ->where('expires_at', '>', now())
            ->firstOrFail();

        // Create or update RSVP response
        $response = RsvpResponse::updateOrCreate(
            [
                'invitation_id' => $invitation->id,
                'guest_id' => $invitation->guest_id,
            ],
            [
                'response' => RsvpResponseType::from($validated['response']),
                'message' => $validated['message'] ?? null,
                'attendees_count' => $validated['attendees_count'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        // Trigger follow-up actions
        if ($response->wasRecentlyCreated) {
            // Send WhatsApp confirmation
            // Send email notification
            // Update invitation status
        }

        return response()->json(['status' => 'recorded']);
    }
}
```

---

## Services

### CallingService

**Location:** `app/Services/CallingService.php`

**Purpose:** Orchestrates outbound voice calls

```php
class CallingService
{
    public function initiateRsvpCall(Invitation $invitation): void
    {
        $twilio = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

        $call = $twilio->calls->create(
            $invitation->guest->phone,  // To
            env('TWILIO_PHONE_NUMBER'),   // From
            [
                'url' => route('twilio.voice.rsvp', ['slug' => $invitation->slug]),
                'status_callback' => route('twilio.voice.status'),
                'status_callback_event' => ['completed', 'no-answer', 'failed', 'busy'],
            ]
        );
    }

    public function handleCallCompleted(array $callData): void
    {
        // Check call duration
        $duration = $callData['call_duration'] ?? 0;

        if ($duration < 10) {
            // Short call - send WhatsApp fallback
            $this->sendWhatsAppFallback($callData);
        }
    }
}
```

### VerifyWhatsAppService

**Location:** `app/Services/VerifyWhatsAppService.php`

**Purpose:** WhatsApp fallback for unanswered/short calls

```php
class VerifyWhatsAppService
{
    public function sendFallback(Invitation $invitation): void
    {
        $twilio = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

        $message = "נא לא ענה לענות להזמנה.
                    לחץ כאן כדי להגיב: " . route('rsvp.show', ['slug' => $invitation->slug]);

        $twilio->messages->create(
            $invitation->guest->phone,
            [
                'from' => env('TWILIO_WHATSAPP_NUMBER'),
                'body' => $message,
            ]
        );
    }
}
```

### WhatsAppRsvpService

**Location:** `app/Services/WhatsAppRsvpService.php`

**Purpose:** Process WhatsApp responses

---

## Routes

### Web Routes

| Route | Method | Purpose |
|------|--------|---------|
| `/twilio/call/{slug}` | POST | Initiate voice call (TwiML) |
| `/twilio/status` | POST | Call status webhook |
| `/api/twilio/rsvp/process` | POST | Process RSVP from voice |
| `/api/twilio/media` | WS | WebSocket for media stream |

### Public RSVP Routes

| Route | Method | Purpose |
|------|--------|---------|
| `/rsvp/{slug}` | GET | Public RSVP page |
| `/api/rsvp/{slug}` | POST | Submit RSVP response |

---

## Environment Configuration

### `.env` Variables

```bash
# Twilio Configuration
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_PHONE_NUMBER=+9721234567890
TWILIO_WHATSAPP_NUMBER=whatsapp:+14155238886

# Twilio Verify
TWILIO_VERIFY_SERVICE_SID=VA5f1c126dd6b47bcd05492197c1c36f73

# Gemini Live Integration
GEMINI_API_KEY=your_gemini_api_key

# Voice Integration
PHP_WEBHOOK=https://your-domain.com/api/twilio/rsvp/process
CALL_LOG_URL=https://your-domain.com/twilio/call-log
CALL_LOG_SECRET=your_secret_key
```

---

## Webhook Events

### Twilio Call Status Events

| Event | Description |
|-------|-------------|
| `initiated` | Call started |
| `ringing` | Phone is ringing |
| `answered` | Call was answered |
| `completed` | Call ended normally |
| `no-answer` | No answer |
| `busy` | Line busy |
| `failed` | Call failed |

### Status Handling

```php
// app/Http/Controllers/TwilioController.php

public function status(Request $request)
{
    $callStatus = $request->input('CallStatus');

    match ($callStatus) {
        'completed' => $this->handleCompleted($request),
        'no-answer' => $this->handleNoAnswer($request),
        'busy' => $this->handleBusy($request),
        'failed' => $this->handleFailed($request),
        default => Log::info("Unhandled call status: {$callStatus}"),
    };
}
```

---

## TTS Configuration

### Hebrew Text-to-Speech

```php
// SSML Example for enhanced TTS
$ssml = '<speak>
    <lang xml:lang="he-IL">
        <prosody rate="0.9">
            שלום, זה מערכת האירוע להזמנה.
        </prosody>
    </lang>
</speak>';
```

### TTS Settings

| Parameter | Value |
|-----------|-------|
| **Engine** | Google.he-IL-Standard-A |
| **Language** | Hebrew (he-IL) |
| **Rate** | 0.9 (slightly slower for clarity) |
| **Gender** | Female |
| **SSML** | Supported |

---

## Call Flow Diagrams

### Successful RSVP Flow

```
┌──────────────┐
│  Guest Phone │
└──────┬───────┘
       │
       ▼ (call starts)
┌──────────────┐
│   Twilio     │
└──────┬───────┘
       │
       ▼ (TwiML connect)
┌──────────────┐
│  server.js   │ ◄───────┐
│  (WebSocket) │         │
└──────┬───────┘         │
       │                 │
       ▼                 │
┌──────────────┐         │
│  Gemini Live  │         │
│  (AI Agent)  │         │
└──────┬───────┘         │
       │                 │
       │ (tool call)      │
       ▼                 │
┌──────────────┐         │
│ save_rsvp    │         │
│  (tool)      │         │
└──────┬───────┘         │
       │                 │
       ▼ (POST)          │
┌──────────────┐         │
│  Laravel     │         │
│  /api/twilio/ │         │
│  rsvp/process│         │
└──────────────┘         │
       │                 │
       ▼ (confirmation)  │
┌──────────────┐         │
│  Gemini Live  │         │
└──────┬───────┘         │
       │                 │
       ▼ (TTS response)  │
┌──────────────┐         │
│  Guest Phone │ (voice confirmation)
└──────────────┘
```

### Fallback Flow (No Answer)

```
┌──────────────┐
│  Guest Phone │
└──────┬───────┘
       │ (call, no answer)
       ▼
┌──────────────┐
│   Twilio     │
└──────┬───────┘
       │ (call failed)
       ▼
┌──────────────┐
│  Laravel     │
│  webhook     │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  WhatsApp    │
│  Message     │
└──────────────┘
       │
       ▼
┌──────────────┐
│  Guest Phone │ (link to RSVP page)
└──────────────┘
```

---

## Error Handling

### Common Scenarios

| Scenario | Handling |
|----------|----------|
| **Invalid invitation** | AI prompts for valid slug |
| **Network error** | Retry with exponential backoff |
| **Gemini API timeout** | Fallback to static TTS prompts |
| **Database error** | Log error, ask user to try web form |
| **Call dropped** | Detect via status webhook, send WhatsApp |

---

## Testing

### Manual Testing

```bash
# Initiate a test call
php artisan tinker
>>> $invitation = \App\Models\Invitation::first();
>>> app(\App\Services\CallingService::class)->initiateRsvpCall($invitation);
```

### Webhook Testing

```bash
# Simulate webhook from Node.js
curl -X POST https://your-domain.com/api/twilio/rsvp/process \
  -H "Content-Type: application/json" \
  -H "X-Call-Secret: your_secret" \
  -d '{
    "invitation_slug": "abc123",
    "response": "attending",
    "message": "אשמח נחמה",
    "attendees_count": 2
  }'
```

---

## Monitoring

### Call Logging

**Endpoint:** `POST /twilio/call-log`

**Payload:**
```json
{
  "call_sid": "CAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "status": "completed",
  "duration": 45,
  "recording_url": null,
  "invitation_slug": "abc123",
  "rsvp_captured": true
}
```

### Metrics to Track

- Total calls initiated
- Total calls completed
- Total RSVPs captured via voice
- Average call duration
- WhatsApp fallback rate
- Error rate by type

---

## Security Considerations

### Authentication

- Webhook secret validation (`CALL_LOG_SECRET`)
- Invitation slug validation (expiration check)
- Rate limiting on `/api/twilio/rsvp/process`

### Data Validation

- Sanitize all user input from AI
- Validate phone numbers before calling
- Escape SSML/TTS content properly

### PCI Compliance

- Never log credit card data
- Use SUMIT gateway for all payment processing
- Mask phone numbers in logs

---

*See also:*
- [[Architecture/Overview]]
- [[Architecture/AsyncQueue]]
- [[Architecture/Auth]]
