# Local Hebrew Voice Stack

Fully local **Hebrew** voice conversation: **whisper.cpp** (STT) → **llama.cpp** (LLM) → **Piper + phonikud** (TTS), orchestrated by **voicechat2**. No paid APIs. **עברית חובה.**

- **[HEBREW_REQUIRED.md](./HEBREW_REQUIRED.md)** – **חובה**: מודלי STT/TTS/LLM בעברית, הורדות והרצה
- **[MISSION.md](./MISSION.md)** – Goal, constraints, integration with Twilio/RSVP
- **[FOLDER_STRUCTURE.md](./FOLDER_STRUCTURE.md)** – Recommended directory layout
- **[INSTALL_LINUX.md](./INSTALL_LINUX.md)** – Prerequisites and install steps
- **[COMMANDS.md](./COMMANDS.md)** – How to start each service
- **[PIPELINE.md](./PIPELINE.md)** – Audio → STT → LLM → TTS flow
- **[TESTING.md](./TESTING.md)** – Manual and automated tests
- **[PERFORMANCE.md](./PERFORMANCE.md)** – Latency measurements (fill after runs)

Install script (from repo root):

```bash
sudo ./scripts/install-local-voice-stack.sh /opt/local-voice
```

Then start services per COMMANDS.md and optionally point `server.js` at the local pipeline via `LOCAL_VOICE_WS_URL`.
