# Linux Installation – Local Hebrew Voice Stack

Target: **Ubuntu 22.04 / Debian 12** (or similar). Root or sudo required for packages.

## Quick run

```bash
# From project root
chmod +x scripts/install-local-voice-stack.sh
sudo ./scripts/install-local-voice-stack.sh
```

Then start services per [COMMANDS.md](./COMMANDS.md).

## Prerequisites (manual)

- `build-essential` `cmake` `git` `python3` `python3-venv` `pip` (or pip3)
- For whisper/llama CPU: `libopenblas-dev` (OpenBLAS)
- For Piper: `espeak-ng` `libonnxruntime` (or install via Piper docs)
- Optional GPU: CUDA/ROCm and matching build flags for whisper.cpp / llama.cpp

## Component versions (as of doc date)

| Component   | Repo / source              | Suggested version      |
|------------|----------------------------|------------------------|
| whisper.cpp | https://github.com/ggml-org/whisper.cpp | tag v1.8.1 or latest |
| llama.cpp   | https://github.com/ggerganov/llama.cpp  | latest main            |
| Piper        | https://github.com/rhasspy/piper        | latest; or OHF-Voice/piper1-gpl |
| voicechat2  | https://github.com/lhl/voicechat2       | main                    |

## Build options (CPU, low latency)

- **whisper.cpp**: `-DGGML_OPENBLAS=ON` (and AVX2 if available).
- **llama.cpp**: `-DGGML_OPENBLAS=ON`; `-DLLAMA_NATIVE=ON` on same-arch host.
- **Piper**: Follow official build/install (C++ or Python); ensure Hebrew or fallback voice path is set.

## Models to download

1. **Whisper**: run `models/download-ggml-model.sh base` (or small/large-v3) in whisper.cpp repo.
2. **LLM**: download a GGUF (e.g. Llama-3-8B-Instruct Q4_K_M) into `llama.cpp/models/`.
3. **Piper**: download from [rhasspy/piper-voices](https://huggingface.co/rhasspy/piper-voices); until Hebrew exists, use e.g. `en_US-ryan-high`.

## Hebrew TTS

No official Piper Hebrew model yet. Options:

- Use an English (or other) voice for testing.
- Follow [Piper Hebrew discussion](https://github.com/rhasspy/piper/discussions/795) and [neurlang/piper hebrew branch](https://github.com/neurlang/piper) to train/fine-tune a Hebrew voice.

Script location: `scripts/install-local-voice-stack.sh` in this repo.
