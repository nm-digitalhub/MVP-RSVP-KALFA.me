# Technical Guide: Calling System Architecture

This system handles automated outbound calls via Twilio with a dynamic AI agent and a WhatsApp fallback mechanism.

**Voice-to-voice & pronunciation:** See [VOICE_RSVP_RESEARCH.md](VOICE_RSVP_RESEARCH.md) for design notes on speech-only flow (no DTMF) and Hebrew TTS/SSML.

---

## 1. Routes Reference

| Method | Path | Name | Auth | Purpose |
|--------|------|------|------|---------|
| GET | `/twilio/calling` | twilio.calling.index | auth, org | Calling UI page |
| POST | `/twilio/calling/initiate` | twilio.calling.initiate | auth, org | Start a call (JSON or NDJSON stream) |
| GET | `/twilio/calling/logs` | twilio.calling.logs | auth, org | Poll call log (query: `call_sid`) |
| POST | `/twilio/calling/status` | twilio.calling.status | — | Twilio status callback (CSRF except) |
| GET/POST | `/twilio/rsvp/connect` | twilio.rsvp.connect | — | TwiML for Stream (must be &lt; 64KB; CSRF except) |
| POST | `/twilio/rsvp/response` | twilio.rsvp.digit_response | — | DTMF response TwiML (CSRF except) |
| POST | `/api/twilio/calling/log` | api.twilio.calling.log.append | secret | Node.js appends transcript (body: `call_sid`, `secret`, `lines`) |
| POST | `/api/twilio/rsvp/process` | api.twilio.rsvp.process | — | Node.js sends RSVP result |

All `/twilio/*` web routes are excluded from CSRF in `VerifyCsrfToken::$except` so Twilio callbacks work.

---

## 2. Core Components

### `CallingService` (`app/Services/CallingService.php`)
- **findGuestByPhone:** SQL suffix match on `phone` for upcoming events.
- **normalizePhoneNumber:** Israeli formats → E.164 (e.g. 0532743588 → +972532743588).
- **ensureInvitation:** Creates invitation if missing.
- **initiateCall:** Creates Twilio call with `url` = connect, `statusCallback` = status; normalizes guest phone to E.164 before sending.

### `CallingController` (`app/Http/Controllers/Twilio/CallingController.php`)
- **index:** Renders calling UI (optional `show_new_guest`, `number`).
- **call:** Initiates call; supports `?stream=1` for NDJSON response.
- **getLogs:** GET with `call_sid` → JSON `{ status, lines }` from cache.
- **appendLog:** POST (API) with `call_sid`, `secret`, `lines[]` → appends to cache; auth via `CALL_LOG_SECRET` (body or header `X-Call-Log-Secret`).
- **statusCallback:** Twilio POST; updates call log cache and triggers WhatsApp fallback when no-answer/canceled or completed with duration ≤ 5s.

### `RsvpVoiceController` (`app/Http/Controllers/Twilio/RsvpVoiceController.php`)
- **connect:** Returns TwiML `<Response><Say>…</Say><Connect><Stream url="wss://...">` with `<Parameter>` for guest/event/invitation. Greeting is **voice-to-voice** (invites speech; no "press 1 or 2"). Uses Hebrew TTS (Google.he-IL-Standard-A) with optional SSML (prosody, break) for pronunciation. Built in PHP only (no Blade) to keep response &lt; 64KB. Accepts **GET and POST** (Twilio may request with POST).
- **digitResponse:** DTMF 1/2 → RsvpResponse + invitation status + optional SMS (legacy/fallback; main flow uses Stream → Gemini).
- **process:** API for Node.js to submit RSVP result.

### `WhatsAppRsvpService`
Fallback when voice fails (no-answer, canceled, or very short completed call).

---

## 3. Call Data Flow

1. User submits number on `/twilio/calling` → `POST /twilio/calling/initiate` (optionally with `stream=1`).
2. `CallingService` normalizes phone, finds or creates guest, ensures invitation.
3. Twilio call is created with:
   - **url:** `https://kalfa.me/twilio/rsvp/connect?guest_id=&event_id=&invitation_id=` (TwiML for `<Stream>`).
   - **statusCallback:** `https://kalfa.me/twilio/calling/status?invitation_id=`.
4. When the call is answered, Twilio requests the **connect** URL (GET or POST) to get TwiML. Response **must** be TwiML and **&lt; 64KB** (error 11750 otherwise).
5. Status callbacks (initiated, ringing, answered, completed) hit **statusCallback**; controller updates cache and may trigger WhatsApp fallback.
6. Node.js media server can append transcript via `POST /api/twilio/calling/log` with `CALL_LOG_SECRET`. UI polls **getLogs** with `call_sid`.

---

## 4. TwiML and 64KB Limit

- **Connect endpoint** must return only `text/xml` TwiML; no HTML or debug output.
- Twilio error **11750**: response body &gt; 64KB. Fix: build TwiML in PHP (`buildConnectTwiML`), no Blade layout; catch exceptions and return minimal error TwiML.
- **Connect route** must accept both **GET and POST**; Twilio often uses POST when fetching the `url` for an in-progress call.

---

## 5. Real-time Transcript

- **Streaming initiate:** `POST .../initiate?stream=1` → `Content-Type: application/x-ndjson`, one JSON object per line.
- **Cache key:** `call_log:{CallSid}` → `{ status, lines: [{ role, text, at }], updated_at }`, TTL 1 hour.
- **getLogs:** `GET /twilio/calling/logs?call_sid=CA...` → `{ status, lines }`.
- **appendLog:** `POST /api/twilio/calling/log` with `call_sid`, `secret` (or `X-Call-Log-Secret`), `lines: [{ role, text }]`.

---

## 6. Webhooks & Security

- **CSRF:** All `twilio/*` are in `VerifyCsrfToken::$except`.
- **Append Log:** Validates `config('services.twilio.call_log_secret')` via body `secret` or header `X-Call-Log-Secret`.
- **Normalization:** Always use `CallingService::normalizePhoneNumber` for numbers sent to Twilio.

---

## 7. Config & Environment

| Key | Purpose |
|-----|----------|
| `TWILIO_ACCOUNT_SID` | Twilio account |
| `TWILIO_AUTH_TOKEN` | Twilio auth |
| `TWILIO_NUMBER` | From number (E.164) |
| `TWILIO_WHATSAPP_FROM` | WhatsApp fallback sender |
| `RSVP_NODE_WS_URL` | WebSocket URL for `<Stream>` (e.g. wss://node.kalfa.me/media) |
| `CALL_LOG_SECRET` | Secret for append-log API |

---

## 8. Deployment Checklist

1. Set `TWILIO_NUMBER`, `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` in `.env`.
2. Set `CALL_LOG_SECRET` if using Node.js log appending.
3. Set `RSVP_NODE_WS_URL` to your media server WebSocket URL.
4. Configure `TWILIO_WHATSAPP_FROM` for fallback.
5. Ensure connect endpoint returns only TwiML and accepts GET and POST.
6. After code changes, clear config/cache and restart PHP if using OPcache.
