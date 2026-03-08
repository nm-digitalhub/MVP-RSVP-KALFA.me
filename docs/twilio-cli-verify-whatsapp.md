# Twilio CLI + Verify WhatsApp (OTP)

תחקיר: התקנת Twilio CLI, וקבלת קוד OTP ב-WhatsApp דרך Twilio Verify.

---

## 1. Twilio CLI – התקנה והתחברות

### התקנה (Linux/Debian)

```bash
wget -qO- https://twilio-cli-prod.s3.amazonaws.com/twilio_pub.asc \
  | sudo apt-key add -
sudo touch /etc/apt/sources.list.d/twilio.list
echo 'deb https://twilio-cli-prod.s3.amazonaws.com/apt/ /' \
  | sudo tee /etc/apt/sources.list.d/twilio.list
sudo apt update
sudo apt install -y twilio
```

או עם **npm** (דורש Node 20+):

```bash
npm install -g twilio-cli
```

### התחברות לחשבון

```bash
twilio login
```

יתבקש **Account SID** ו-**Auth Token** מ-[Twilio Console](https://www.twilio.com/console). אחרי ההתחברות ה-CLI שומר פרופיל מקומי ומשתמש ב-API Key.

### בדיקה

```bash
twilio --version
twilio phone-numbers:list
```

---

## 2. קבלת OTP ב-WhatsApp (Verify API)

"קבלת שיחה ל-OTP WhatsApp" = **המשתמש מקבל את קוד האימות בהודעת WhatsApp** (לא שיחה קולית). זה נעשה דרך **Twilio Verify** עם ערוץ `whatsapp`.

### דרישות מוקדמות

| רכיב | איפה |
|------|------|
| **Verify Service** | [Console → Verify → Services](https://www.twilio.com/console/verify/services). SID מתחיל ב-`VA`. |
| **WhatsApp Sender** | מאז מרץ 2024 חובה **Bring Your Own (BYO)** – מספר טלפון משויך ל-WhatsApp Business Account (WABA). |

### הגדרת WhatsApp Sender ל-Verify (BYO)

1. **WhatsApp Sender**  
   [Messaging → Senders → WhatsApp Senders](https://console.twilio.com/us1/develop/sms/senders/whatsapp-senders) – או ליצור חדש דרך [Self-Sign-up](https://www.twilio.com/docs/whatsapp/self-sign-up).

2. **Messaging Service**  
   ליצור/לבחור [Messaging Service](https://console.twilio.com/us1/service/sms/create) (SID מתחיל ב-`MG`) ולשייך אליו את ה-WhatsApp Sender.

3. **שיוך ל-Verify**  
   [Verify → Services](https://www.twilio.com/console/verify/services) → לבחור את ה-Service → לשונית **WhatsApp** → לבחור את ה-Messaging Service (ה-`MG`).

---

## 3. שליחת OTP ב-WhatsApp דרך ה-CLI

לאחר ש-Verify Service ו-WhatsApp Sender מוגדרים:

```bash
twilio api:verify:v2:services:verifications:create \
  --service-sid VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx \
  --to +972501234567 \
  --channel whatsapp
```

- `--service-sid`: ה-Verify Service SID (`VA...`).
- `--to`: מספר ב-E.164 (עם `+`).
- `--channel`: `whatsapp` (במקום `sms`).

המשתמש יקבל הודעת WhatsApp עם קוד האימות (ותבנית Authentication עם כפתור Copy Code אם השתמשת ב-BYO).

### בדיקת הקוד (Check Verification)

```bash
twilio api:verify:v2:services:verification-checks:create \
  --service-sid VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx \
  --to +972501234567 \
  --code 123456
```

---

## 4. שימוש מ-PHP (Laravel) – אותו חשבון Twilio

ה-SDK שכבר בפרויקט תומך ב-Verify. דוגמה לשליחת OTP ב-WhatsApp:

```php
use Twilio\Rest\Client;

$twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
$verifySid = config('services.twilio.verify_sid'); // VA...

$verification = $twilio->verify->v2
    ->services($verifySid)
    ->verifications
    ->create('+972501234567', 'whatsapp');
```

בדיקת קוד:

```php
$check = $twilio->verify->v2
    ->services($verifySid)
    ->verificationChecks
    ->create(['to' => '+972501234567', 'code' => $userEnteredCode]);
// $check->status === 'approved'
```

נדרש ב-`config/services.php`: `'verify_sid' => env('TWILIO_VERIFY_SID')` וב-.env: `TWILIO_VERIFY_SID=VA...`.

---

## 5. Twilio CLI – פקודות שימושיות

| פעולה | פקודה |
|--------|--------|
| **רשימת Verify Services (להשגת TWILIO_VERIFY_SID)** | `twilio api:verify:v2:services:list` או `-o json` |
| יצירת Verify Service | `twilio api:verify:v2:services:create --friendly-name "My OTP"` |
| רשימת מספרים | `twilio phone-numbers:list` |
| עדכון webhook ל-SMS | `twilio phone-numbers:update PN... --sms-url https://...` |
| עדכון webhook ל-Voice | `twilio phone-numbers:update PN... --voice-url https://...` |
| שליחת SMS | `twilio api:core:messages:create --from +1... --to +972... --body "..."` |
| שליחת OTP ב-WhatsApp | `twilio api:verify:v2:services:verifications:create --service-sid VA... --to +972... --channel whatsapp` |
| בדיקת OTP | `twilio api:verify:v2:services:verification-checks:create --service-sid VA... --to +972... --code 123456` |

**Webhooks:** Twilio לא מקבל `localhost`. לפיתוח מקומי להשתמש ב-**ngrok** (למשל `ngrok http 80`) ולעדכן את ה-URL ב-Console או דרך `phone-numbers:update`.

---

## 6. סיכום – לאפשר "קבלת OTP ב-WhatsApp"

1. **התקן Twilio CLI** והתחבר עם `twilio login`.
2. **צור Verify Service** ב-Console (או דרך API) ושמור את ה-SID (`VA...`).
3. **הגדר WhatsApp Sender (BYO)** ו-Messaging Service, וקשור אותם ל-Verify Service בלשונית WhatsApp.
4. **שלח OTP** עם `channel=whatsapp` (CLI או PHP כמו למעלה).
5. המשתמש **מקבל** את הקוד בהודעת WhatsApp; בדיקת הקוד כמו ב-SMS (Verify Check API).

מסמכי Twilio: [Verify WhatsApp](https://www.twilio.com/docs/verify/whatsapp), [BYO Sender](https://www.twilio.com/docs/verify/whatsapp/byo), [Twilio CLI Quickstart](https://www.twilio.com/docs/twilio-cli/quickstart).
