# ניתוח: RSVP Voice + Gemini Live API

## 1. זרימת הקבצים

### 1.1 `public/twilio/rsvp-voice.php`
- **תפקיד:** נקודת קצה TwiML. נקרא כשהשיחה מתחברת.
- **פלט:** XML עם `<Connect><Stream url="..."/></Connect>`.
- **כתובת ה-Stream:** `RSVP_NODE_WS_URL?guest_id=&event_id=&invitation_id=&guest_name=`
- **מעביר:** Twilio פותח WebSocket ל-Node (path `/media`) עם query params.

### 1.2 `server.js` (Node)
- **תפקיד:** ריליי דו-כיווני: Twilio Media Stream ↔ Gemini Live API (BidiGenerateContent).
- **זרימה:**
  1. Twilio מתחבר ל-`/media` עם query params.
  2. על `event: "start"` פותחים WebSocket ל-Gemini (`v1beta.BidiGenerateContent`).
  3. שולחים **setup** (מודל, generation_config, system_instruction, tools).
  4. מחכים ל-**setupComplete**.
  5. שולחים **clientContent** (טקסט פתיחת שיחה) עם `turnComplete: true`.
  6. אודיו מ-Twilio (`event: "media"`, payload base64) נשלח כ-**realtimeInput** (mediaChunks, mulaw 8kHz).
  7. תשובות Gemini (אודיו) נשלחות חזרה ל-Twilio כ-`event: "media"`.
  8. על **toolCall** (save_rsvp) קוראים ל-PHP webhook ושולחים **toolResponse**.

### 1.3 `public/twilio/rsvp-process.php`
- **תפקיד:** מקבל את תוצאת ה-RSVP מה-Node (אחרי ש-Gemini קורא ל-save_rsvp).
- **קלט:** POST JSON: `guest_id`, `invitation_id`, `intent` (yes/no/maybe), `number_of_guests`.
- **פעולה:** `RsvpResponse::updateOrCreate`, אופציונלי SMS לאורח.

### 1.4 `public/twilio/rsvp-response.php`
- **תפקיד:** Webhook ללחיצת מקש (DTMF) ב-RSVP קולי **לא** דרך Gemini (זרימה אחרת).
- לא מעורב בזרימת Gemini Live.

---

## 2. תיעוד Google – Live API (ai.google.dev)

- **Endpoint:** `wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContent?key=...`
- **גרסה:** v1beta (לא v1alpha).

### 2.1 BidiGenerateContentSetup (הודעה ראשונה)
לפי [Live API - WebSockets API reference](https://ai.google.dev/api/live):

| שדה | תיאור |
|-----|--------|
| `model` | `string` – פורמט `models/{model}` |
| `generationConfig` | אובייקט (camelCase) |
| `systemInstruction` | Content (camelCase) |
| `tools[]` | Tool |

**חשוב:** בתיעוד הרשמי של Google AI (Gemini API) השדות ב-**camelCase**: `generationConfig`, `systemInstruction`. ב-Vertex AI מופיע לעיתים snake_case (`generation_config`, `system_instruction`) כי זה Protobuf. ב-WebSocket של **generativelanguage** (Google AI) ה-JSON משתמש ב-**camelCase**.

### 2.2 GenerationConfig
- `responseModalities`: מערך, ערכים כמו `"AUDIO"`, `"TEXT"` (אותיות גדולות).
- שדות לא נתמכים ב-Live: responseLogprobs, responseMimeType, logprobs, responseSchema, stopSequence, routingConfig, audioTimestamp.

### 2.3 Tools / Function declarations
- ב-cookbook ([Issue #929](https://github.com/google-gemini/cookbook/issues/929)): ב-WebSocket נדרש **functionDeclarations** (camelCase). `function_declarations` (snake_case) מחזיר "Unknown name".

### 2.4 BidiGenerateContentClientContent
- `turns[]`: מערך Content (role, parts).
- `turnComplete`: bool (camelCase בתיעוד ai.google.dev).

### 2.5 BidiGenerateContentRealtimeInput
- `mediaChunks[]`: Blob (אודיו/וידאו).

### 2.6 BidiGenerateContentToolResponse
- `functionResponses[]`: מערך FunctionResponse (id, name, response).

---

## 3. אי-ההתאמות שזוהו (לפני תיקון)

| מיקום | אצלנו | לפי תיעוד Google AI (v1beta) | הערה |
|--------|--------|------------------------------|------|
| Setup | `generation_config` | `generationConfig` | snake_case vs camelCase |
| Setup | `system_instruction` | `systemInstruction` | snake_case vs camelCase |
| Setup | `response_modalities` | `responseModalities` (בתוך generationConfig) | אותיות גדולות AUDIO כבר תוקן |
| Setup – tools | `function_declarations` | `functionDeclarations` | cookbook: snake_case לא נתמך |
| clientContent | `turnComplete` | `turnComplete` | תואם |
| toolResponse | `functionResponses` | `functionResponses` | תואם |

**מסקנה:** ה-setup נשלח ב-**snake_case** בעוד ש-Google AI WebSocket (generativelanguage v1beta) מצפה ל-**camelCase**. ייתכן שזה גורם לכך שהשדות מתעלמים או לא מאומתים נכון, ואז ההודעה הבאה (clientContent) מחזירה 1007 "Request contains an invalid argument".

---

## 4. צעדים מומלצים

1. **להתאים את ה-setup ב-`server.js`** לשמות השדות והמבנה של ai.google.dev:
   - `generationConfig`, `systemInstruction`, `responseModalities`.
   - `tools[].functionDeclarations` (ו-parameters במבנה הנתמך ל-function calling).
2. **להשאיר** `clientContent` עם `turnComplete: true` ו-`turns` כמו היום (כבר תואם).
3. **להשאיר** `toolResponse.functionResponses` (כבר camelCase).
4. אחרי תיקון – לבדוק שוב שיחה; אם 1007 נעלם, לשקול החזרת flush של buffered audio אחרי ה-clientContent הראשון (בזהירות).
