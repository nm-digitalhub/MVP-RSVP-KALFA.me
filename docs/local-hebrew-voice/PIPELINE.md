# Pipeline – Audio → STT → LLM → TTS → Output

## Data flow

1. **Input**: Raw audio (e.g. Twilio µ-law 8 kHz, or browser Opus). voicechat2 may resample and/or use VAD (ricky0123/vad) to detect speech segments.
2. **STT**: Audio → whisper.cpp (or faster-whisper) → text. Use multilingual model and `-l he` or auto-detect for Hebrew.
3. **LLM**: Text + conversation history → llama.cpp (OpenAI-compatible API) → streamed text tokens. System prompt can define RSVP agent behavior in Hebrew.
4. **TTS**: Text (or token stream) → Piper → PCM/audio. Streaming: start TTS on first sentence or on token chunks to reduce time-to-first-audio.
5. **Output**: Audio sent back to client (Twilio Media Stream, browser, or local speaker).

## voicechat2 role

voicechat2 is the orchestrator:

- Accepts WebSocket connections (e.g. from browser or from a Node relay).
- Receives audio; optionally uses VAD; sends segments to SRT server (whisper).
- Sends transcript to LLM server; receives streamed completion.
- Sends text (or chunks) to TTS server; receives audio.
- Streams audio back over WebSocket (e.g. Opus).

So the “pipeline” is implemented inside voicechat2 plus the three backend services (whisper, llama, piper).

## Streaming and latency

- **Streaming LLM**: llama.cpp server supports streaming; voicechat2 should consume tokens as they arrive.
- **Streaming TTS**: Piper can be driven sentence-by-sentence (or chunk-by-chunk) so playback starts before the full reply is generated.
- **Batching**: Disable or reduce where possible to avoid extra delay.

## RSVP integration

For the existing Twilio RSVP flow:

- The **system prompt** for the LLM should instruct it to: ask if the guest is coming and how many people; then output a structured response (e.g. JSON or a fixed format) or “tool call” that the relay translates into a POST to `rsvp-process.php` with `guest_id`, `invitation_id`, `intent`, `number_of_guests`.
- The Node relay (`server.js`) can be extended: when `LOCAL_VOICE_WS_URL` is set, connect to voicechat2 instead of Gemini, and when the local pipeline returns an “RSVP result” (parsed from LLM output or a small backend), POST to `PHP_WEBHOOK` (`rsvp-process.php`).
