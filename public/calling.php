<?php

require __DIR__.'/../vendor/autoload.php';

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sid = config('services.twilio.sid');
$token = config('services.twilio.token');
$from = config('services.twilio.number');

$message = '';
$messageType = '';
$showNewGuestForm = false;
$searchedPhone = '';
$log = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['number'])) {
    $raw = trim($_POST['number']);
    $log[] = 'Received number: '.$raw;

    // Normalize phone format
    if (str_starts_with($raw, '0')) {
        $raw = '+972'.substr($raw, 1);
        $log[] = 'Normalized local number to E.164: '.$raw;
    }

    if (! preg_match('/^\+[1-9]\d{1,14}$/', $raw)) {
        $message = 'מספר טלפון לא תקין. יש להזין בפורמט בינלאומי, לדוגמה: +972501234567';
        $messageType = 'error';
        $log[] = 'Invalid phone format after normalization: '.$raw;
    } else {
        $searchedPhone = $raw;
        $log[] = 'Using phone: '.$searchedPhone;

        // Find upcoming events
        $upcomingEvents = Event::with(['guests.invitation'])
            ->where('event_date', '>=', now())
            ->orderBy('event_date')
            ->get();
        $log[] = 'Found '.$upcomingEvents->count().' upcoming events';

        if ($upcomingEvents->isEmpty()) {
            $message = 'לא נמצא אירוע קרוב במערכת.';
            $messageType = 'error';
            $log[] = 'No upcoming events found.';
        } else {
            // Find guest by phone across all upcoming events
            $phoneSuffix = substr(preg_replace('/\D/', '', $searchedPhone), -9);

            $guest = null;
            $event = null;

            foreach ($upcomingEvents as $e) {
                $foundGuest = $e->guests->first(function (Guest $g) use ($phoneSuffix) {
                    $guestSuffix = substr(preg_replace('/\D/', '', $g->phone ?? ''), -9);

                    return $guestSuffix === $phoneSuffix;
                });

                if ($foundGuest) {
                    $guest = $foundGuest;
                    $event = $e;
                    break;
                }
            }

            // If no guest found, default to the nearest upcoming event
            if (! $event) {
                $event = $upcomingEvents->first();
                $log[] = 'Guest not found by phone; defaulting to first upcoming event ID='.$event->id;
            } else {
                $log[] = 'Guest found: '.$guest->name.' (ID='.$guest->id.') for event ID='.$event->id;
            }

            // If action is CREATE_GUEST, handle new guest creation
            if (isset($_POST['action']) && $_POST['action'] === 'create_guest') {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? null);
                $group = trim($_POST['group_name'] ?? null);

                if ($name === '') {
                    $message = 'חובה להזין שם מלא.';
                    $messageType = 'error';
                    $showNewGuestForm = true;
                    $log[] = 'Create guest failed: missing name.';
                } else {
                    $guest = Guest::create([
                        'event_id' => $event->id,
                        'name' => $name,
                        'phone' => $searchedPhone,
                        'email' => $email ?: null,
                        'group_name' => $group ?: null,
                        'sort_order' => 0,
                    ]);
                    $message = "אורח חדש {$name} נוצר בהצלחה!";
                    $messageType = 'success';
                    $log[] = 'New guest created: '.$name.' (ID='.$guest->id.') for event ID='.$event->id;
                }
            }

            // If we STILL don't have a guest, show the extended form
            if (! $guest && ! $showNewGuestForm) {
                $showNewGuestForm = true;
                $message = 'המספר לא נמצא במערכת. אנא הזן פרטים כדי להוסיף אורח חדש ולחייג אליו.';
                $messageType = 'error';
                $log[] = 'Guest not found after search; showing new guest form.';
            }

            // If we DO have a guest, make sure they have an invitation, then call
            if ($guest && ! $showNewGuestForm) {
                // Ensure invitation exists
                $invitation = $guest->invitation;
                if (! $invitation) {
                    $invitation = Invitation::create([
                        'event_id' => $event->id,
                        'guest_id' => $guest->id,
                        'token' => Str::random(32),
                        'slug' => Str::random(10),
                        'status' => App\Enums\InvitationStatus::Sent,
                    ]);
                    $log[] = 'Created new invitation ID='.$invitation->id.' for guest ID='.$guest->id;
                } else {
                    $log[] = 'Using existing invitation ID='.$invitation->id.' for guest ID='.$guest->id;
                }

                // Initiate Twilio Call via OpenClaw voice-call webhook
                try {
                    $client = new Client($sid, $token);

                    // Voice-call plugin webhook
                    $callbackUrl = 'https://node.kalfa.me/voice/webhook';
                    $log[] = 'Using callback URL: '.$callbackUrl;

                    $call = $client->calls->create(
                        $searchedPhone,
                        $from,
                        ['url' => $callbackUrl]
                    );

                    $log[] = 'Twilio call created, SID='.$call->sid;
                    $message .= "<br>השיחה יצאה בהצלחה לאורח: <strong>{$guest->name}</strong><br>Call SID: {$call->sid}";
                    $messageType = 'success';
                } catch (\Exception $e) {
                    $message = 'שגיאה ביצירת השיחה: '.$e->getMessage();
                    $messageType = 'error';
                    $log[] = 'Twilio call error: '.$e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>שיחות RSVP — Twilio</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;}
        .card { background: #1e293b; border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 440px; box-shadow: 0 25px 50px rgba(0,0,0,.4); }
        h1 { font-size: 1.5rem; margin-bottom: .5rem; color: #f8fafc; }
        .subtitle { color: #94a3b8; font-size: .9rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-size: .85rem; color: #94a3b8; margin-bottom: .5rem; font-weight: 500;}
        input[type="text"], input[type="email"] { width: 100%; padding: .85rem 1rem; border: 1px solid #334155; border-radius: 10px; background: #0f172a; color: #f8fafc; font-size: 1rem; outline: none; transition: border-color .2s; }
        input.ltr { direction: ltr; text-align: left; font-size: 1.1rem;}
        input:focus { border-color: #6366f1; }
        button { width: 100%; margin-top: .5rem; padding: .85rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 1rem; font-weight: 600; border: none; border-radius: 10px; cursor: pointer; transition: opacity .2s; }
        button:hover { opacity: .9; }
        .msg { margin-bottom: 1.5rem; padding: .85rem 1rem; border-radius: 10px; font-size: .9rem; line-height: 1.5; }
        .msg.success { background: #064e3b; color: #6ee7b7; border: 1px solid #047857;}
        .msg.error   { background: #7f1d1d; color: #fca5a5; border: 1px solid #b91c1c;}
        .extended-form { background: #0f172a; border-radius: 12px; padding: 1.5rem; border: 1px dashed #475569; margin-bottom: 1.5rem;}
        .extended-form h3 { font-size: 1.1rem; color: #f8fafc; margin-bottom: 1rem;}
        .terminal-log { margin-top: 1.5rem; background:#020617; border-radius:12px; padding:1rem; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size:0.8rem; color:#e5e7eb; border:1px solid #1e293b; }
        .terminal-log-title { margin-bottom:0.5rem; color:#38bdf8; }
        .terminal-log-pre { white-space:pre-wrap; word-break:break-word; max-height:220px; overflow:auto; }
    </style>
</head>
<body>
    <div class="card">
        <h1>📞 שיחות RSVP</h1>
        <p class="subtitle">הזן מספר טלפון של אורח כדי להתקשר ולבקש אישור הגעה</p>

        <?php if ($message) { ?>
            <div class="msg <?= $messageType ?>"><?= $message ?></div>
        <?php } ?>

        <form method="post">
            <div class="form-group">
                <label for="number">מספר טלפון לאישור הגעה</label>
                <input type="text" id="number" name="number" class="ltr" placeholder="+972501234567" pattern="^(\+[1-9]\d{1,14}|0\d{8,9})$" value="<?= htmlspecialchars($searchedPhone) ?>" <?= $showNewGuestForm ? 'readonly' : 'required autofocus' ?>>
            </div>

            <?php if ($showNewGuestForm) { ?>
                <div class="extended-form">
                    <h3>✨ יצירת אורח חדש</h3>
                    <input type="hidden" name="action" value="create_guest">

                    <div class="form-group">
                        <label for="name">שם מלא (חובה)</label>
                        <input type="text" id="name" name="name" placeholder="לדוגמה: ישראל ישראלי" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="email">אימייל (אופציונלי)</label>
                        <input type="email" id="email" name="email" class="ltr" placeholder="example@email.com">
                    </div>

                    <div class="form-group">
                        <label for="group_name">קבוצה / משפחה (אופציונלי)</label>
                        <input type="text" id="group_name" name="group_name" placeholder="לדוגמה: משפחת כהן">
                    </div>
                </div>
                <button type="submit">➕ הוסף אורח וחייג עכשיו</button>
                <button type="button" onclick="window.location.href='calling.php'" style="background: transparent; border: 1px solid #475569; margin-top: 10px;">ביטול חיפוש</button>
            <?php } else { ?>
                <button type="submit">📲 חייג עכשיו</button>
            <?php } ?>
        </form>

        <?php if (! empty($log)) { ?>
            <div class="terminal-log">
                <div class="terminal-log-title">TERMINAL LOG</div>
                <pre class="terminal-log-pre">
$ <?= htmlspecialchars(implode("\n$ ", $log), ENT_QUOTES, 'UTF-8') ?>
                </pre>
            </div>
        <?php } ?>
    </div>
</body>
</html>
