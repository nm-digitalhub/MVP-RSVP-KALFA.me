# Project Analyzer – Decisions

- **2025-03-03**: Output path for project analysis set to `.claude/context/artifacts/project-analysis.json` (single file; no schema validation run). File/language counts derived from globs and find (app, config, database, routes, tests, resources/views); vendor and node_modules excluded.

- **2025-03-05 (RSVP voice greeting)**: In `public/twilio/rsvp-voice.php` do not call GLM (or any external API) before returning TwiML; use static greetings or pre-cached value only. Cache key `rsvp_voice_greeting:{invitation_id}` can be filled by a background job when an invitation is sent to get personalized AI greeting with zero delay on first call.
