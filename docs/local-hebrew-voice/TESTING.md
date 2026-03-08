# Testing – Local Hebrew Voice Stack

## Automated checks

Create tests that verify:

1. **Hebrew speech input is transcribed** – run whisper.cpp (or whisper-server) on a short Hebrew WAV; assert transcript contains expected text or language.
2. **LLM generates Hebrew responses** – send a Hebrew prompt to llama.cpp server; assert response is non-empty and optionally Hebrew.
3. **TTS outputs speech** – send Hebrew (or fallback English) text to Piper; assert audio file or stream is produced.
4. **End-to-end latency** – measure time from “user stops speaking” to “first TTS audio chunk”; target &lt; 1.5s.

## Manual tests

### Whisper (Hebrew)

```bash
# Record or use a Hebrew WAV, then:
cd /opt/local-voice/whisper.cpp
./build/bin/whisper-cli -m models/ggml-base.bin -l he -f sample_he.wav
```

### Llama (Hebrew prompt)

```bash
# With server running on 8081:
curl -s http://127.0.0.1:8081/v1/completions -H "Content-Type: application/json" \
  -d '{"prompt":"תגיד שלום בעברית.","max_tokens":50}'
```

### Piper

```bash
echo "שלום עולם" | /opt/local-voice/piper/piper -m voices/en_US-ryan-high.onnx --output_file out.wav
# Or use HTTP server if configured; then play out.wav
```

### voicechat2 E2E

1. Start whisper-server, llama server, Piper TTS, and voicechat2 per COMMANDS.md.
2. Open voicechat2 UI in browser; speak in Hebrew; confirm transcription and Hebrew (or fallback) reply and TTS playback.
3. Measure latency (browser DevTools or server logs).

## Latency &gt; 1.5s

- Use smaller Whisper model (base or tiny).
- Use quantized LLM (Q4/Q5).
- Enable streaming (tokens → TTS as they arrive).
- Disable batching where applicable.
- Optionally enable GPU for Whisper/LLM if available.
