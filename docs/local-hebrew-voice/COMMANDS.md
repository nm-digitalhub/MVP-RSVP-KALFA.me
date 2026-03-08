# Commands to Start the Local Voice Server

Assumes install path `/opt/local-voice` (or set `VOICE_ROOT`). **Hebrew is mandatory** – use the models from [HEBREW_REQUIRED.md](./HEBREW_REQUIRED.md).

## 1. Start whisper.cpp server (SRT) – Hebrew

```bash
cd /opt/local-voice/whisper.cpp
./build/bin/whisper-server --host 127.0.0.1 --port 8080 -m models/ggml-large-v3-ivrit.bin
```

Use the ivrit-ai model (ggml-large-v3-ivrit.bin) for Hebrew. If missing, see HEBREW_REQUIRED.md to download from ivrit-ai/whisper-large-v3-ggml.

## 2. Start llama.cpp server (LLM)

```bash
cd /opt/local-voice/llama.cpp
./build/bin/server -m models/Meta-Llama-3-8B-Instruct-Q4_K_M.gguf -c 2048 --host 127.0.0.1 --port 8081
```

Port 8081 matches typical voicechat2 expectation for OpenAI-compatible API.

## 3. Start Hebrew TTS server (mandatory)

Hebrew TTS: thewh1teagle phonikud + Piper ONNX. See [HEBREW_REQUIRED.md](./HEBREW_REQUIRED.md).

```bash
cd /opt/local-voice/piper/hebrew
source .venv/bin/activate
# Run TTS server (Flask/FastAPI) with model.onnx + phonikud. Port 5000. See HEBREW_REQUIRED.md.
python -m http.server 5000
```

Point voicechat2 TTS_SERVER_URL at this server.

## 4. Start voicechat2 (orchestrator)

```bash
cd /opt/local-voice/voicechat2
source .venv/bin/activate   # or mamba activate voicechat2
export SRT_SERVER_URL="http://127.0.0.1:8080"   # whisper-server
export LLM_SERVER_URL="http://127.0.0.1:8081"   # llama.cpp server
export TTS_SERVER_URL="http://127.0.0.1:5000"   # Piper
./run-voicechat2.sh
```

Or run `voicechat2.py` with the correct env/config so it connects to the above SRT/LLM/TTS endpoints.

## 5. Optional: systemd units

Create service files so whisper-server, llama server, Piper, and voicechat2 start on boot and restart on failure. Example names:

- `whisper-server.service`
- `llama-server.service`
- `piper-tts.service`
- `voicechat2.service`

Point `WorkingDirectory` and `ExecStart` to the paths above.

## 6. Point this app at the local pipeline

In `.env` or environment for the Node process:

```env
# Use local voice instead of Gemini (optional; implement relay in server.js)
LOCAL_VOICE_WS_URL=ws://127.0.0.1:8765
```

Then in `server.js`, when `LOCAL_VOICE_WS_URL` is set, connect Twilio Media Stream to that WebSocket instead of Gemini, and forward RSVP to `PHP_WEBHOOK` when the local LLM “calls” the same contract (e.g. JSON or tool).
