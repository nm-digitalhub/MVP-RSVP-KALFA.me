# Mission: Fully Local Hebrew Voice Conversation AI

## Goal

Build a **fully local** Hebrew voice conversation system using **only open-source software** and **zero paid APIs**. Run on Linux and support telephony/browser integration (e.g. existing Twilio RSVP flow).

## Architecture

```
Audio Input (Twilio/browser/mic)
    → whisper.cpp (Speech-to-Text)
    → llama.cpp (LLM)
    → Piper TTS (Text-to-Speech)
    → Audio Output
```

Orchestration: **voicechat2** (WebSocket pipeline connecting SRT, LLM, TTS).

## Hard Constraints

- No paid APIs
- No cloud AI services
- Only open-source software
- Must run locally on Linux (Ubuntu/Debian)
- **Hebrew is mandatory**: Hebrew speech recognition (STT), Hebrew LLM responses, and Hebrew text-to-speech (TTS). See [HEBREW_REQUIRED.md](./HEBREW_REQUIRED.md).
- Target latency &lt; 1.5s
- Prefer CPU; GPU optional

## Required Components

| Component   | Project        | Role                    | Hebrew (חובה) |
|------------|----------------|--------------------------|----------------|
| Speech-to-Text | whisper.cpp (ggml-org) | Multilingual ASR         | **Mandatory**: use [ivrit-ai/whisper-large-v3-ggml](https://huggingface.co/ivrit-ai/whisper-large-v3-ggml) for Hebrew. See [HEBREW_REQUIRED.md](./HEBREW_REQUIRED.md). |
| LLM        | llama.cpp (ggerganov)  | Conversation             | **Mandatory**: Hebrew system prompt + instruct model (e.g. Llama-3-8B-Instruct). |
| Text-to-Speech | Piper + phonikud (thewh1teagle) | Hebrew TTS               | **Mandatory**: [phonikud-tts-checkpoints](https://huggingface.co/thewh1teagle/phonikud-tts-checkpoints) (Piper ONNX) + [phonikud](https://github.com/thewh1teagle/phonikud) (G2P) + [piper-onnx](https://github.com/thewh1teagle/piper-onnx). See [HEBREW_REQUIRED.md](./HEBREW_REQUIRED.md). |
| Pipeline   | voicechat2 (lhl)       | WebSocket orchestration  | Connects SRT + LLM + TTS; TTS must be Hebrew-capable server (phonikud + Piper). |

## Pipeline Flow (voicechat2)

1. **Microphone/stream input** (e.g. Twilio Media Stream) → Opus/VAD
2. **whisper.cpp** (or faster-whisper) → transcription
3. **llama.cpp** (or OpenAI-compatible server) → text response
4. **Piper** (or Coqui/StyleTTS2) → speech
5. **Speaker/audio stream output**

Streaming: LLM tokens can drive TTS incrementally to reduce latency.

## Integration with Existing RSVP Stack

Current stack:

- `public/twilio/rsvp-voice.php` → TwiML `<Stream url="..."/>` to Node
- `server.js` → Twilio WS ↔ **Gemini Live API**
- `public/twilio/rsvp-process.php` → receives RSVP result (e.g. from Gemini tool)

To use the **local** voice stack instead of Gemini:

1. Run **voicechat2** (and its SRT/LLM/TTS backends) on the same server or reachable host.
2. Add a **relay** in Node: accept Twilio Media Stream as today, but send audio to the local voice pipeline (e.g. voicechat2 WebSocket or HTTP) and receive audio back, then forward to Twilio.
3. Keep **rsvp-process.php** as-is; the local LLM (or a small backend) must call the same webhook with `guest_id`, `invitation_id`, `intent`, `number_of_guests` when the user confirms RSVP (e.g. via function/tool or structured output from llama.cpp).

So: replace “Gemini Live” with “local voice pipeline + webhook call” in the architecture; PHP endpoints stay the same.

## Folder Structure

See [FOLDER_STRUCTURE.md](./FOLDER_STRUCTURE.md).

## Installation

See [INSTALL_LINUX.md](./INSTALL_LINUX.md) and `scripts/install-local-voice-stack.sh`.

## Commands to Start

See [COMMANDS.md](./COMMANDS.md).

## Testing

See [TESTING.md](./TESTING.md).

## Performance and Latency

Target: &lt; 1.5s voice-to-voice. If exceeded:

- Use smaller Whisper model (e.g. base, tiny)
- Use quantized LLM (Q4/Q5)
- Enable streaming (tokens → TTS as they arrive)
- Disable batching where applicable
- Optional: GPU for Whisper/LLM

## Hebrew is mandatory

Full Hebrew support is required at every stage. See **[HEBREW_REQUIRED.md](./HEBREW_REQUIRED.md)** for:

- **STT**: Mandatory use of [ivrit-ai/whisper-large-v3-ggml](https://huggingface.co/ivrit-ai/whisper-large-v3-ggml) with whisper.cpp.
- **TTS**: Mandatory use of thewh1teagle’s Hebrew Piper pipeline: phonikud-tts-checkpoints (model.onnx), phonikud (G2P), piper-onnx; optional phonikud-onnx for diacritics.
- **LLM**: Hebrew system prompt and instruct model (e.g. Llama-3-8B-Instruct).

## Output Deliverables

1. Full installation script for Linux → `scripts/install-local-voice-stack.sh`
2. Folder structure → `docs/local-hebrew-voice/FOLDER_STRUCTURE.md`
3. Commands to start the voice server → `docs/local-hebrew-voice/COMMANDS.md`
4. Pipeline documentation → this file + `docs/local-hebrew-voice/PIPELINE.md`
5. Performance measurements → after first run, fill `docs/local-hebrew-voice/PERFORMANCE.md`

## References

- [voicechat2](https://github.com/lhl/voicechat2) – WebSocket local voice chat (whisper.cpp, llama.cpp, Piper)
- [whisper.cpp](https://github.com/ggml-org/whisper.cpp) – stable v1.8.x
- [llama.cpp](https://github.com/ggerganov/llama.cpp) – server with OpenAI-compatible API
- [Piper](https://github.com/rhasspy/piper) – fast local TTS
- [Piper voices (Hugging Face)](https://huggingface.co/rhasspy/piper-voices) – no Hebrew yet
- [Piper Hebrew discussion](https://github.com/rhasspy/piper/discussions/795)
