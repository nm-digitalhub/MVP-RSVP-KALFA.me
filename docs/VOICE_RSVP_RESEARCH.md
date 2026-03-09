# Voice RSVP: חקירה – הגייה ו־Voice-to-Voice

מסמך חקירה לפני יישום: מעבר מלחצנים (DTMF) לאינטראקציה קולית מלאה, ושיפור הגיית עברית (TTS).

---

## 1. מצב נוכחי

### 1.1 זרימת השיחה

1. **Twilio** מתקשר ל־**connect** (`/twilio/rsvp/connect`) ומקבל TwiML.
2. TwiML מכיל:
   - **Say** – ברכה בעברית (כרגע: "לחץ 1 לאישור הגעה, או 2 לביטול").
   - **Connect → Stream** – חיבור WebSocket ל־**Node.js**.
3. **Node.js** (`server.js`) משדר אודיו דו־כיווני בין Twilio ל־**Gemini Live API**.
4. **Gemini** מתנהג כנציג טלפוני: שואל אם האורח מגיע וכמה אנשים, מזהה כוונה (כן/לא), קורא ל־`save_rsvp`; Node שולח POST ל־`/api/twilio/rsvp/process`.

כלומר: **האינטראקציה האמיתית כבר voice-to-voice** – Gemini מקשיב לדיבור ומגיב בדיבור. הבעיה היא שהברכה הראשונה (Say) **מזמינה לחיצות 1/2**, וזה לא תואם את מה שקורה אחרי (Stream ל־Gemini).

### 1.2 DTMF (digitResponse)

- **Route:** `POST /twilio/rsvp/response` → `RsvpVoiceController::digitResponse`.
- משמש רק אם TwiML היה מכיל **Gather** עם `input="dtmf"` ומפנה ל־action הזה.
- ב־connect הנוכחי **אין** Gather – רק Say + Connect/Stream. לכן **digitResponse לא בשימוש** בזרימה הנוכחית.
- אם בעתיד תרצה fallback ל־DTMF (למשל אם Stream נכשל), אפשר להחזיר TwiML עם Gather digits ו־action ל־`/twilio/rsvp/response`.

---

## 2. דרישות: "Voice-to-Voice" ו"לא לחצנים"

- **משמעות:** האורח עונה **בדיבור** (כן/לא, כמה אנשים), וה־AI (Gemini) מזהה את הכוונה – **בלי** להנחות "לחץ 1 או 2".
- **יישום מומלץ:**
  1. **להסיר** מהברכה את ההנחיה "לחץ 1 לאישור הגעה, או 2 לביטול".
  2. **לנסח מחדש** את הברכה כך שתזמין תשובה בדיבור, למשל: "תגיד בבקשה אם אתה מגיע וכמה אנשים."
  3. **לא** לשנות את הזרימה הטכנית (Stream → Node → Gemini) – היא כבר voice-to-voice; רק להתאים את הטקסט והטון של הברכה.

אין צורך ב־Gather עם `input="speech"` בזרימה הראשית, כי Gemini כבר מטפל בדיבור דרך ה־Stream.

---

## 3. חלופה: Twilio Gather עם input=speech (ללא Stream/Gemini)

אם בעתיד תרצה **בלי** Node/Gemini – רק TwiML:

- **Gather** עם `input="speech"`, `action` ל־URL שמחזיר TwiML, ו־`language` לעברית.
- Twilio ישלח ל־action את **SpeechResult** (תמלול) ו־**Confidence**.
- בשרת (Laravel): לפרש את התמלול (מילות מפתח או מודל קטן) → yes/no + אולי כמות → להחזיר Say לאישור ולעדכן RSVP.

**תמיכה בעברית ב־Gather:**

- ב־Twilio (טבלה deprecated): "Hebrew (Israel) – **iw-IL** – not supported in Google's v2 STT global APIs".
- ב־**Google Cloud Speech-to-Text V2**: עברית נתמכת תחת **iw-IL** (Hebrew Israel) עם מודלים chirp_2 / chirp_3.
- ב־Twilio Gather 2.0: השפה עוברת ל־provider (Google STT V2 או Deepgram). יש לבדוק ב־Console/דוק אם `language="iw-IL"` או `he-IL` נתמך עם `speechModel` כמו `googlev2_telephony`.
- **Deepgram**: לבדוק ב־[Models & Languages](https://developers.deepgram.com/docs/models-languages-overview) אם יש עברית.

אם תבחרו במסלול Gather speech בלבד (בלי Gemini), יש ליישם endpoint שמקבל `SpeechResult`, מפרש intent, ומחזיר TwiML מתאים.

---

## 4. הגייה (Pronunciation) – עברית TTS

### 4.1 מצב נוכחי

- **Say** משתמש ב־`language="he-IL"` ו־`voice="Google.he-IL-Standard-A"` (קול Google עברי).
- קול **Polly.Lea** הוסר כי Lea הוא צרפתי (fr-FR), לא עברי.

### 4.2 שיפור הגייה

1. **קולות אלטרנטיביים (Twilio Say):**
   - **Google.he-IL-Standard-A** – כבר בשימוש.
   - **Google.he-IL-Wavenet-A** – איכות גבוהה יותר (Neural), אם זמין ב־Twilio.
   - **Google.he-IL-Chirp3-HD-*** – קולות Chirp3 HD של Google (אם Twilio חושף עברית ברשימת הקולות).
   - יש לוודא ב־[Twilio TTS – Available voices and languages](https://www.twilio.com/docs/voice/twiml/say/text-speech#available-voices-and-languages) אילו קודי he-IL/iw-IL זמינים.

2. **SSML (בתוך Say):**
   - Twilio תומך ב־SSML עבור Standard/Premium (לא Basic). תגים רלוונטיים:
     - **`<break time="300ms"/>`** – הפסקה בין משפטים/פסקאות לשיפור הבהירות.
     - **`<prosody rate="90%" />`** – האטה קלה (למשל 85%–95%) אם ההגייה נשמעת מהירה מדי.
     - **`<say-as interpret-as="date" />`** – לתאריכים (אם יש תאריך בברכה).
     - **`<phoneme>`** – הגייה פונטית (IPA או x-sampa) למילים ספציפיות שמתעוותות.
   - דוגמה: ברכה עם הפסקות וקצב:
     ```xml
     <Say language="he-IL" voice="Google.he-IL-Standard-A">
       <prosody rate="92%">שלום.</prosody><break time="300ms"/>
       זה אישור הגעה לאירוע X.<break time="400ms"/>
       תגיד בבקשה אם אתה מגיע וכמה אנשים.
     </Say>
     ```

3. **טקסט הברכה:**
   - להימנע ממשפטים ארוכים מדי; לפצל למשפטים קצרים עם הפסקה לוגית.
   - אם יש שמות/מקומות שמתעוותים – לשקול `<sub alias="...">` או `<phoneme>`.

4. **אם ההגייה עדיין לא מספקת:**
   - להקליט קבצי אודיו (עברית) ולשדר עם **Play** במקום Say (יישום מורכב יותר, דורש אחסון וניהול קבצים).

---

## 5. סיכום והמלצות יישום

| נושא | המלצה |
|------|--------|
| **Voice-to-voice, לא לחצנים** | לעדכן את **טקסט הברכה** ב־`buildConnectTwiML`: להסיר "לחץ 1 או 2", להזמין תשובה בדיבור. לא לשנות Stream/Node/Gemini. |
| **הגייה** | להשאיר `Google.he-IL-Standard-A`; להוסיף **SSML** אופציונלי: `<break>`, `<prosody rate="90%">`; לבדוק קול Wavenet/Chirp3 אם זמין. |
| **digitResponse** | להשאיר כ־fallback; לא בשימוש בזרימה הנוכחית. |
| **Gather speech (ללא Gemini)** | רק אם תרצו מסלול חלופי ללא Node; לדרוש אימות תמיכת עברית (iw-IL/he-IL) ב־Twilio + provider. |

---

## 6. קבצים רלוונטיים

- `app/Http/Controllers/Twilio/RsvpVoiceController.php` – `buildConnectTwiML`, `digitResponse`, הודעות Say.
- `server.js` – Stream ↔ Gemini, system instruction, `save_rsvp`.
- `docs/CALLING_SYSTEM_TECHNICAL.md` – routes, flow, 64KB, getLogs/appendLog.
