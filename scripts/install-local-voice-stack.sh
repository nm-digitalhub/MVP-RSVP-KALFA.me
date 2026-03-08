#!/usr/bin/env bash
# Install local Hebrew voice stack: whisper.cpp, llama.cpp, Piper, voicechat2
# Target: Ubuntu 22.04 / Debian 12. Run with sudo for apt, or run as root.
# Usage: ./scripts/install-local-voice-stack.sh [INSTALL_DIR]
# Default INSTALL_DIR: /opt/local-voice

set -e

INSTALL_DIR="${1:-/opt/local-voice}"
VOICE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "[install] Local voice stack → $INSTALL_DIR"

# System deps (Debian/Ubuntu)
install_deps() {
  apt-get update -qq
  apt-get install -y --no-install-recommends \
    build-essential cmake git \
    python3 python3-venv python3-pip \
    libopenblas-dev \
    wget ca-certificates
  # Piper / TTS often need espeak-ng
  apt-get install -y --no-install-recommends espeak-ng 2>/dev/null || true
}

# Clone and build whisper.cpp; Hebrew mandatory: use ivrit-ai model
install_whisper() {
  local dir="$INSTALL_DIR/whisper.cpp"
  if [[ -d "$dir/.git" ]]; then
    echo "[whisper] Already cloned, pulling..."
    (cd "$dir" && git pull -q)
  else
    echo "[whisper] Cloning ggml-org/whisper.cpp..."
    mkdir -p "$INSTALL_DIR"
    git clone --depth 1 https://github.com/ggml-org/whisper.cpp.git "$dir"
  fi
  echo "[whisper] Building (Release, OpenBLAS)..."
  (cd "$dir" && cmake -B build -DCMAKE_BUILD_TYPE=Release -DGGML_OPENBLAS=ON && cmake --build build -j"$(nproc)" --config Release)
  mkdir -p "$dir/models"
  # Hebrew mandatory: ivrit-ai Whisper large-v3 ggml (see docs/local-hebrew-voice/HEBREW_REQUIRED.md)
  if [[ ! -f "$dir/models/ggml-large-v3-ivrit.bin" ]]; then
    echo "[whisper] Downloading Hebrew model (ivrit-ai/whisper-large-v3-ggml)..."
    if wget -q -O "$dir/models/ggml-large-v3-ivrit.bin" \
      "https://huggingface.co/ivrit-ai/whisper-large-v3-ggml/resolve/main/ggml-large-v3.bin" 2>/dev/null; then
      echo "[whisper] Hebrew model installed."
    else
      echo "[whisper] Could not auto-download ivrit-ai model. Download manually from https://huggingface.co/ivrit-ai/whisper-large-v3-ggml and place .bin in $dir/models/"
      (cd "$dir" && bash models/download-ggml-model.sh base 2>/dev/null || true)
    fi
  fi
  echo "[whisper] Done. Use: $dir/build/bin/whisper-server -m $dir/models/ggml-large-v3-ivrit.bin (or ggml-base.bin fallback)"
}

# Clone and build llama.cpp
install_llama() {
  local dir="$INSTALL_DIR/llama.cpp"
  if [[ -d "$dir/.git" ]]; then
    echo "[llama] Already cloned, pulling..."
    (cd "$dir" && git pull -q)
  else
    echo "[llama] Cloning ggerganov/llama.cpp..."
    mkdir -p "$INSTALL_DIR"
    git clone --depth 1 https://github.com/ggerganov/llama.cpp.git "$dir"
  fi
  echo "[llama] Building (Release, OpenBLAS)..."
  (cd "$dir" && cmake -B build -DCMAKE_BUILD_TYPE=Release -DGGML_OPENBLAS=ON && cmake --build build -j"$(nproc)" --config Release)
  mkdir -p "$dir/models"
  if ! ls "$dir/models"/*.gguf 1>/dev/null 2>&1; then
    echo "[llama] No GGUF model found. Download one manually, e.g.:"
    echo "  wget -P $dir/models/ https://huggingface.co/bartowski/Meta-Llama-3-8B-Instruct-GGUF/resolve/main/Meta-Llama-3-8B-Instruct-Q4_K_M.gguf"
  fi
  echo "[llama] Done. Server: $dir/build/bin/server"
}

# Piper + Hebrew TTS (mandatory): thewh1teagle phonikud-tts-checkpoints + phonikud + piper-onnx
install_piper() {
  local dir="$INSTALL_DIR/piper"
  mkdir -p "$dir"
  mkdir -p "$dir/voices"
  if [[ -d "$dir/.git" ]]; then
    echo "[piper] Already cloned, pulling..."
    (cd "$dir" && git pull -q)
  elif [[ -n "$(ls -A "$dir" 2>/dev/null)" ]]; then
    echo "[piper] Directory exists and is not empty; skipping clone (Hebrew TTS will still be set up)."
  else
    echo "[piper] Cloning rhasspy/piper..."
    git clone --depth 1 https://github.com/rhasspy/piper.git "$dir"
  fi
  if [[ -f "$dir/CMakeLists.txt" ]]; then
    echo "[piper] Building native binary..."
    (cd "$dir" && cmake -B build -DCMAKE_BUILD_TYPE=Release && cmake --build build -j"$(nproc)" 2>/dev/null) || true
  fi
  # Hebrew TTS mandatory: phonikud-tts-checkpoints (Piper ONNX) + phonikud (G2P) + piper-onnx
  local hebrew_dir="$dir/hebrew"
  mkdir -p "$hebrew_dir"
  if [[ ! -f "$hebrew_dir/model.onnx" ]]; then
    echo "[piper] Downloading Hebrew TTS model (thewh1teagle/phonikud-tts-checkpoints)..."
    wget -q -O "$hebrew_dir/model.onnx" \
      "https://huggingface.co/thewh1teagle/phonikud-tts-checkpoints/resolve/main/model.onnx" 2>/dev/null || true
    wget -q -O "$hebrew_dir/model.config.json" \
      "https://huggingface.co/thewh1teagle/phonikud-tts-checkpoints/resolve/main/model.config.json" 2>/dev/null || true
  fi
  if [[ -f "$hebrew_dir/model.onnx" ]]; then
    echo "[piper] Creating Hebrew TTS venv (phonikud, piper-onnx)..."
    (cd "$hebrew_dir" && python3 -m venv .venv 2>/dev/null; .venv/bin/pip install -q phonikud piper-onnx onnxruntime 2>/dev/null) || true
  else
    echo "[piper] Manual: download model.onnx + model.config.json from https://huggingface.co/thewh1teagle/phonikud-tts-checkpoints into $hebrew_dir"
  fi
  echo "[piper] Done. Hebrew TTS: $hebrew_dir (see docs/local-hebrew-voice/HEBREW_REQUIRED.md)"
}

# voicechat2: clone and Python deps
install_voicechat2() {
  local dir="$INSTALL_DIR/voicechat2"
  if [[ -d "$dir/.git" ]]; then
    echo "[voicechat2] Already cloned, pulling..."
    (cd "$dir" && git pull -q)
  else
    echo "[voicechat2] Cloning lhl/voicechat2..."
    mkdir -p "$INSTALL_DIR"
    git clone --depth 1 https://github.com/lhl/voicechat2.git "$dir"
  fi
  echo "[voicechat2] Creating venv and installing requirements..."
  (cd "$dir" && python3 -m venv .venv && .venv/bin/pip install -q -r requirements.txt)
  echo "[voicechat2] Done. Run: cd $dir && source .venv/bin/activate && ./run-voicechat2.sh"
}

# Write env helper (Hebrew mandatory paths)
write_env() {
  local f="$INSTALL_DIR/env.sh"
  local whisper_model="\${VOICE_ROOT}/whisper.cpp/models/ggml-large-v3-ivrit.bin"
  [[ ! -f "$INSTALL_DIR/whisper.cpp/models/ggml-large-v3-ivrit.bin" ]] && whisper_model="\${VOICE_ROOT}/whisper.cpp/models/ggml-base.bin"
  cat > "$f" << EOF
# Local voice stack – Hebrew mandatory. Source before running services.
export VOICE_ROOT="$INSTALL_DIR"
export WHISPER_MODEL="$whisper_model"
export WHISPER_LANG="he"
export LLAMA_MODEL="\${VOICE_ROOT}/llama.cpp/models"
export PIPER_VOICES="\${VOICE_ROOT}/piper/voices"
export HEBREW_TTS_DIR="\${VOICE_ROOT}/piper/hebrew"
# voicechat2: SRT/LLM/TTS server URLs (TTS must be Hebrew-capable, see HEBREW_REQUIRED.md)
export SRT_SERVER_URL="http://127.0.0.1:8080"
export LLM_SERVER_URL="http://127.0.0.1:8081"
export TTS_SERVER_URL="http://127.0.0.1:5000"
EOF
  echo "[env] Wrote $f"
}

# Main
if [[ "$(id -u)" -eq 0 ]]; then
  install_deps
else
  echo "[install] Not root; skipping apt. Install deps manually: build-essential cmake git python3 python3-venv libopenblas-dev"
fi
install_whisper
install_llama
install_piper
install_voicechat2
write_env

echo ""
echo "Install done. Hebrew is mandatory – see docs/local-hebrew-voice/HEBREW_REQUIRED.md"
echo "Next:"
echo "  1. Download LLM GGUF (e.g. Llama-3-8B-Instruct) into $INSTALL_DIR/llama.cpp/models/"
echo "  2. Start whisper-server with Hebrew model: -m $INSTALL_DIR/whisper.cpp/models/ggml-large-v3-ivrit.bin -l he"
echo "  3. Run Hebrew TTS server (phonikud + piper-onnx) or point voicechat2 at it – see HEBREW_REQUIRED.md"
echo "  4. Start services – docs/local-hebrew-voice/COMMANDS.md"
echo "  5. source $INSTALL_DIR/env.sh"
