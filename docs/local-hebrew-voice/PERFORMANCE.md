# Performance Measurements

Fill after first runs. Target: voice-to-voice latency &lt; 1.5s.

| Metric              | Value   | Notes                    |
|---------------------|--------|--------------------------|
| STT (whisper)       | _ ms   | Time from speech end to transcript |
| LLM first token     | _ ms   | Time from transcript to first token |
| LLM full reply      | _ ms   | Time to full text        |
| TTS first chunk     | _ ms   | Time from first token to first audio |
| **End-to-end**      | _ ms   | Speech end → first TTS audio |
| Hardware            | _      | CPU model, RAM, GPU if any |
| Whisper model       | _      | e.g. base / small        |
| LLM model           | _      | e.g. Llama-3-8B Q4_K_M   |

## Optimizations tried

- [ ] Smaller Whisper model
- [ ] Quantized LLM (Q4/Q5)
- [ ] Streaming tokens → TTS
- [ ] Disable batching
- [ ] GPU for Whisper/LLM
