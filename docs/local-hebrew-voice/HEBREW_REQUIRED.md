# עברית – חובה (Hebrew Mandatory)

תמיכה מלאה בעברית היא **חובה** בכל שלב: זיהוי דיבור (STT), מודל שפה (LLM), וטקסט-לדיבור (TTS).

---

## 1. STT – זיהוי דיבור בעברית (חובה)

### מודל מחייב: ivrit-ai Whisper ב־ggml

- **מקור**: [ivrit-ai/whisper-large-v3-ggml](https://huggingface.co/ivrit-ai/whisper-large-v3-ggml) (Hugging Face)
- **תאימות**: whisper.cpp (וגם Vibe וכל מנוע ggml-based)
- **תיאור**: גרסת ggml של מודל ivrit-ai/whisper-large-v3, מותאם לעברית.

### הורדה והרצה

```bash
# בתיקיית whisper.cpp
cd /opt/local-voice/whisper.cpp
mkdir -p models
# הורדת מודל ivrit-ai (בחר קובץ .bin מתאים מהעץ ב-Hugging Face)
# דוגמה – יש לבדוק בעץ הקבצים את השם המדויק:
wget -P models/ https://huggingface.co/ivrit-ai/whisper-large-v3-ggml/resolve/main/ggml-large-v3.bin

# הרצה עם שפת יעד עברית
./build/bin/whisper-cli -m models/ggml-large-v3.bin -l he -f sample_he.wav
./build/bin/whisper-server --host 127.0.0.1 -m models/ggml-large-v3.bin
```

אם ב־ivrit-ai/whisper-large-v3-ggml יש שמות קבצים שונים (למשל קוונטיזציה), יש להשתמש בקובץ ה־.bin שמופיע בעץ הקבצים שם.

---

## 2. LLM – תשובות בעברית (חובה)

- השתמש במודל instruct שתומך בעברית (למשל Llama-3-8B-Instruct, Mistral-7B-Instruct).
- **חובה**: להגדיר system prompt בעברית ולבקש במפורש שהתשובות יהיו בעברית.
- דוגמה ל־system prompt ל־RSVP:  
  "אתה נציג טלפוני. כל התשובות חייבת להיות בעברית. שאל אם האורח מגיע וכמה אנשים..."

אין מודל GGUF "עברית בלבד" – חובה להנחות את המודל באמצעות prompt.

---

## 3. TTS – דיבור בעברית (חובה)

אין עדיין קול עברי רשמי ב־rhasspy/piper-voices. הפתרון המחייב הוא שימוש במודלים ובכלים של **thewh1teagle** (Piper + G2P עברית).

### א. מודל TTS עברית (Piper ONNX)

- **מקור**: [thewh1teagle/phonikud-tts-checkpoints](https://huggingface.co/thewh1teagle/phonikud-tts-checkpoints)  
  קבצים: `model.onnx`, `model.config.json` (מודל Piper מאומן על ILSpeech עברית).

```bash
mkdir -p /opt/local-voice/piper/hebrew
wget -O /opt/local-voice/piper/hebrew/model.onnx \
  https://huggingface.co/thewh1teagle/phonikud-tts-checkpoints/resolve/main/model.onnx
wget -O /opt/local-voice/piper/hebrew/model.config.json \
  https://huggingface.co/thewh1teagle/phonikud-tts-checkpoints/resolve/main/model.config.json
```

### ב. G2P וניקוד (חובה ל־TTS איכותי)

- **phonikud** (PyPI): המרת טקסט עברית (עם ניקוד) ל־IPA לצורך Piper.  
  `pip install phonikud`
- **phonikud-onnx** (Hugging Face: [thewh1teagle/phonikud-onnx](https://huggingface.co/thewh1teagle/phonikud-onnx)): הוספת ניקוד (diacritics) לטקסט עברית – נדרש אם הטקסט מגיע בלי ניקוד.

### ג. הרצת TTS עברית (Python)

- **piper-onnx** (thewh1teagle): הרצת Piper מ־Python עם onnxruntime.  
  `pip install piper-onnx`

זרימה מומלצת:

1. טקסט עברית → (אופציונלי) phonikud-onnx → טקסט מנוקד  
2. טקסט (מנוקד) → phonikud.phonemize() → IPA  
3. IPA → piper-onnx עם `model.onnx` + `model.config.json` מ־phonikud-tts-checkpoints → אודיו

דוגמת קוד (מהפרויקט thewh1teagle/phonikud-experiments):

```bash
uv pip install piper-onnx phonikud
# הורדת מודל TTS (כבר בוצעה למעלה)
```

```python
from piper_onnx import Piper
import phonikud

piper = Piper("hebrew/model.onnx", "hebrew/model.config.json")
text = "שלום עולם"  # או טקסט מנוקד
phonemes = phonikud.phonemize(text)  # נדרש ניקוד; אחרת השתמש ב-phonikud-onnx קודם
samples, sample_rate = piper.create(phonemes + " .", is_phonemes=True, length_scale=1.2)
# שמירה/השמעה של samples, sample_rate
```

### ד. שילוב ב־voicechat2

- voicechat2 תומך ב־Piper כ־TTS, אבל בדרך כלל עם קולות rhasspy (ללא עברית).
- **חובה** להריץ שרת TTS עברי נפרד (למשל Flask/FastAPI) שמקבל טקסט, מריץ את צינור phonikud + piper-onnx, ומחזיר אודיו – ו־voicechat2 יפנה אליו כ־TTS_SERVER_URL.
- אלטרנטיבה: התאמת קוד voicechat2 (או fork) כך שיקרא ל־Piper עם מודל ה־Hebrew ו־phonikud (כולל G2P/ניקוד) לפני סינתזת אודיו.

### ה. דמו ואופציות נוספות

- **דמו TTS עברית**: [thewh1teagle/phonikud-tts](https://huggingface.co/spaces/thewh1teagle/phonikud-tts) (Hugging Face Space).
- **heb-piper-tts-gemma-g2p-onnx**: [thewh1teagle/heb-piper-tts-gemma-g2p-onnx](https://github.com/thewh1teagle/heb-piper-tts-gemma-g2p-onnx) – TTS עברית עם Piper ו־G2P מבוסס Gemma3.
- **neurlang/piper** (branch `hebrew`): לאימון קול Piper נוסף בעברית – [מדריך](https://blog.hashtron.cloud/post/2025-09-28-training-a-a-tiny-piper-tts-model-for-any-language/).

---

## 4. סיכום חובות

| רכיב | חובה | מקור |
|------|------|------|
| STT עברית | מודל ivrit-ai ב־ggml | ivrit-ai/whisper-large-v3-ggml |
| LLM עברית | Prompt בעברית + מודל instruct | Llama-3 / Mistral וכו' |
| TTS עברית | Piper ONNX + G2P עברית | thewh1teagle: phonikud-tts-checkpoints, phonikud, piper-onnx, (אופציונלי) phonikud-onnx |

בלי עברית מלאה ב־STT וב־TTS המערכת אינה עומדת בדרישה. יש להשתמש במודלים ובכלים המפורטים למעלה.
