# חיבור Verify ל-WhatsApp – מה בוצע ומה נשאר

## מה בוצע אוטומטית

1. **Verify Service**  
   - נוצר: **Kalfa OTP**  
   - SID: `VA5f1c126dd6b47bcd05492197c1c36f73`  
   - מוגדר ב-`.env`: `TWILIO_VERIFY_SID=VA5f1c126dd6b47bcd05492197c1c36f73`

2. **Messaging Service ל-Verify WhatsApp**  
   - נוצר: **Kalfa Verify WhatsApp**  
   - SID: `MG744fe08efc3f7b3f11047c8da2e7770b`  
   - כרגע בלי WhatsApp Sender ב-Sender Pool.

3. **קישור Verify ↔ Messaging Service**  
   - Twilio מאפשר לקשר Verify ל-Messaging Service רק אם ב-Messaging Service יש **לפחות WhatsApp Sender אחד**.  
   - כיום אין בחשבון WhatsApp Sender, ולכן הקישור עדיין לא בוצע.

---

## מה נדרש ממך (ב-Console)

### אפשרות א: Sandbox (לבדיקות, מהר)

1. היכנס ל-[Try WhatsApp – Twilio Console](https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn).
2. אשר תנאים ולחץ **Confirm**.
3. חבר את ה-WhatsApp שלך ל-Sandbox:  
   שלח ב-WhatsApp למספר ה-Sandbox את ההודעה:  
   `join <המילה שמופיעה אצלך>`  
   (או סרוק QR אם מופיע).
4. הוסף את ה-Sandbox כ-Sender ל-Messaging Service:
   - [Messaging → Services](https://console.twilio.com/us1/develop/sms/services) → **Kalfa Verify WhatsApp** (MG744fe08...).
   - **Sender Pool** → **Add senders** → בחר את ה-**WhatsApp Sandbox** (או את המספר שמופיע תחת WhatsApp Senders) והוסף.
5. קישור Verify ל-Messaging Service:
   - [Verify → Services](https://www.twilio.com/console/verify/services) → **Kalfa OTP**.
   - לשונית **WhatsApp** → ב-**Messaging Service** בחר **Kalfa Verify WhatsApp** (MG744fe08...) → שמור.

אחרי שלבים 4–5, שליחת OTP עם `channel=whatsapp` אמורה לעבוד (למספרים שהצטרפו ל-Sandbox).

### אפשרות ב: Self-Sign-up (Production, מספר משלך)

1. [WhatsApp Self-Sign-up](https://www.twilio.com/docs/whatsapp/self-sign-up) – יצירת WABA (WhatsApp Business Account) וחיבור מספר טלפון.
2. ב-Console: [Messaging → Senders → WhatsApp Senders](https://console.twilio.com/us1/develop/sms/senders/whatsapp-senders) – לוודא שהמספר מופיע כ-WhatsApp Sender.
3. [Messaging → Services](https://console.twilio.com/us1/develop/sms/services) → **Kalfa Verify WhatsApp** → **Sender Pool** → **Add senders** → הוסף את ה-WhatsApp Sender.
4. [Verify → Services](https://www.twilio.com/console/verify/services) → **Kalfa OTP** → לשונית **WhatsApp** → בחר **Kalfa Verify WhatsApp** → שמור.

---

## בדיקה מהאפליקציה

- **סטטוס:** `php artisan verify:whatsapp-status`  
  מציג אם Verify מוגדר ואם WhatsApp מקושר, ומדפיס את צעדי ה-Sandbox אם עדיין לא.
- **שליחת OTP לבדיקה:**  
  `php artisan verify:whatsapp-status --send=+972501234567 --channel=whatsapp`  
  (או `--channel=sms` אם WhatsApp עדיין לא מוגדר.)
- אם `TWILIO_VERIFY_SID` לא מופיע: הרץ `php artisan config:clear` ואז שוב את הפקודה.

## בדיקה אחרי ההשלמה (CLI)

שליחת OTP ב-WhatsApp (לאחר שיש WhatsApp Sender ו-Verify מקושר):

```bash
twilio api:verify:v2:services:verifications:create \
  --service-sid VA5f1c126dd6b47bcd05492197c1c36f73 \
  --to +972501234567 \
  --channel whatsapp
```

או מ-PHP (ראה `docs/twilio-cli-verify-whatsapp.md`).

---

## SID שימושיים

| משאב              | SID |
|-------------------|-----|
| Verify Service    | `VA5f1c126dd6b47bcd05492197c1c36f73` |
| Messaging Service (Verify WhatsApp) | `MG744fe08efc3f7b3f11047c8da2e7770b` |
