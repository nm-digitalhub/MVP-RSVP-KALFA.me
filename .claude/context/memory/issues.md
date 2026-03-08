# Code review issues

## server.js (Node Twilio–Gemini relay) — 2025-03-05

### Important (fix before production)

1. **Webhook `fetch` has no timeout** — PHP endpoint hang blocks tool handler; add AbortController/timeout and on timeout still send toolResponse with error so Gemini continues.
2. **Webhook HTTP result ignored** — Always sending `result: "ok"` to Gemini; check `res.ok` and send ok/error so model knows when RSVP actually failed.
3. **Missing API key still accepts connections** — When `GEMINI_API_KEY` is unset, server still listens; either refuse to start or reject `/media` with clear close reason.
4. **No auth on `/media`** — Any client can open WS; document if trusted-only or add token/signature verification.

### Minor (follow-up)

5. Race: `modelTurn` before `streamSid` — buffer or defer until `streamSid` set if initial TTS is lost.
6. Unused `geminiReady` — Remove or use (e.g. gate media until setup done).
7. Hardcoded default `PHP_WEBHOOK` — Prefer require env in production.
8. Style — Constants for magic numbers, JSDoc for `safeSend`.
