# Local Hebrew Voice Stack – Folder Structure

Recommended layout on the Linux server (e.g. under `/opt` or a dedicated user home).

```
/opt/local-voice/                    # or ~/local-voice
├── whisper.cpp/                    # ggml-org/whisper.cpp clone
│   ├── build/
│   │   └── bin/
│   │       ├── whisper-cli
│   │       └── whisper-server     # HTTP server for voicechat2
│   └── models/
│       ├── ggml-base.bin          # or small / large-v3 for Hebrew
│       └── ggml-*.bin
├── llama.cpp/                      # ggerganov/llama.cpp clone
│   ├── build/
│   │   └── bin/
│   │       └── server              # OpenAI-compatible API
│   └── models/
│       └── *.gguf                  # e.g. Llama-3-8B-Instruct-Q4_K_M.gguf
├── piper/                          # rhasspy/piper clone (or OHF-Voice/piper1-gpl)
│   ├── piper                       # CLI binary
│   └── voices/                     # or model path
│       └── en_US-ryan-high.onnx   # fallback until Hebrew voice
├── voicechat2/                     # lhl/voicechat2 clone
│   ├── voicechat2.py
│   ├── srt-server.py
│   ├── tts-server.py
│   ├── run-voicechat2.sh
│   ├── requirements.txt
│   └── ui/
├── models/                         # optional: shared model cache
│   ├── whisper/
│   ├── llama/
│   └── piper/
├── logs/
└── env.sh                          # or .env: paths, ports, model names
```

## Integration with This Project (kalfa.me)

The existing app lives under `httpdocs/`. The local voice stack can live elsewhere; only connectivity matters.

- **Twilio** → `rsvp-voice.php` → `<Stream url="..."/>` → **Node server** (`server.js`).
- Today: Node forwards to **Gemini Live** and PHP webhook for RSVP.
- With local stack: Node forwards to **voicechat2 WebSocket** (or a thin relay that talks to whisper/llama/piper). RSVP still sent to `rsvp-process.php` via HTTP from the relay or from a small backend that parses LLM output / tool call.

Suggested placement:

- **Local stack**: `/opt/local-voice/` (or `~/local-voice`).
- **This repo**: unchanged; add config (e.g. `LOCAL_VOICE_WS_URL`) so `server.js` can point to the local pipeline instead of Gemini when desired.
