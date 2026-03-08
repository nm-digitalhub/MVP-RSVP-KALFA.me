<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Enums\InvitationStatus;
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
$lastCallSid = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['show_new_guest'], $_GET['number'])) {
    $showNewGuestForm = true;
    $searchedPhone = trim((string) $_GET['number']);
}

$isStream = isset($_GET['stream']) && $_GET['stream'] === '1';
if ($isStream && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    $streamLine = static function (array $payload): void {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE)."\n";
        if (function_exists('flush')) {
            flush();
        }
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! isset($_POST['number'])) {
        if ($isStream) {
            $streamLine([
                'type' => 'done',
                'success' => false,
                'message' => 'חסר מספר טלפון.',
                'messageType' => 'error',
                'showNewGuestForm' => false,
                'searchedPhone' => '',
                'callSid' => null,
            ]);
            exit;
        }
        $message = 'חסר מספר טלפון.';
        $messageType = 'error';
    } else {
        $logFn = $isStream ? static function (string $msg) use ($streamLine): void {
            $streamLine(['type' => 'log', 'msg' => $msg]);
        } : static function (string $msg) use (&$log): void {
            $log[] = $msg;
        };

        try {
            $raw = trim((string) $_POST['number']);
            $logFn('Received number: '.$raw);

            $digits = preg_replace('/\D/', '', $raw);
            $len = strlen($digits);

            if ($len === 10 && str_starts_with($digits, '0')) {
                $raw = '+972'.substr($digits, 1);
                $logFn('Normalized 0XXXXXXXXX to E.164: '.$raw);
            } elseif ($len === 9 && str_starts_with($digits, '5')) {
                $raw = '+972'.$digits;
                $logFn('Normalized 5XXXXXXXX to E.164: '.$raw);
            } elseif ($len >= 11 && $len <= 12 && str_starts_with($digits, '972')) {
                $raw = '+'.$digits;
                $logFn('Normalized 972... to E.164: '.$raw);
            } elseif ($len >= 10 && $len <= 15 && str_starts_with($raw, '+')) {
                $raw = '+'.$digits;
                $logFn('Normalized +... to E.164: '.$raw);
            } elseif ($len >= 9 && $len <= 15 && preg_match('/^[1-9]\d{8,14}$/', $digits)) {
                $raw = '+'.$digits;
                $logFn('Normalized digits to E.164: '.$raw);
            }

            if (! preg_match('/^\+[1-9]\d{8,14}$/', $raw)) {
                $message = 'מספר טלפון לא תקין. יש להזין בפורמט בינלאומי, לדוגמה: +972501234567';
                $messageType = 'error';
                $logFn('Invalid phone format after normalization: '.$raw);
            } else {
                $searchedPhone = $raw;
                $logFn('Using phone: '.$searchedPhone);

                // Find upcoming events
                $upcomingEvents = Event::with(['guests.invitation'])
                    ->where('event_date', '>=', now())
                    ->orderBy('event_date')
                    ->get();
                $logFn('Found '.$upcomingEvents->count().' upcoming events');

                if ($upcomingEvents->isEmpty()) {
                    $message = 'לא נמצא אירוע קרוב במערכת.';
                    $messageType = 'error';
                    $logFn('No upcoming events found.');
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

                    if (! $event) {
                        $event = $upcomingEvents->first();
                        $logFn('Guest not found by phone; defaulting to first upcoming event ID='.$event->id);
                    } else {
                        $logFn('Guest found: '.$guest->name.' (ID='.$guest->id.') for event ID='.$event->id);
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
                            $logFn('Create guest failed: missing name.');
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
                            $logFn('New guest created: '.$name.' (ID='.$guest->id.') for event ID='.$event->id);
                        }
                    }

                    if (! $guest && ! $showNewGuestForm) {
                        $showNewGuestForm = true;
                        $message = 'המספר לא נמצא במערכת. אנא הזן פרטים כדי להוסיף אורח חדש ולחייג אליו.';
                        $messageType = 'error';
                        $logFn('Guest not found after search; showing new guest form.');
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
                                'status' => InvitationStatus::Sent,
                            ]);
                            $logFn('Created new invitation ID='.$invitation->id.' for guest ID='.$guest->id);
                        } else {
                            $logFn('Using existing invitation ID='.$invitation->id.' for guest ID='.$guest->id);
                        }

                        try {
                            $logFn('Connecting to Twilio…');
                            $client = new Client($sid, $token);
                            $twimlUrl = rtrim(config('app.url'), '/').'/twilio/rsvp-voice.php?'.http_build_query([
                                'guest_id' => $guest->id,
                                'event_id' => $event->id,
                                'invitation_id' => $invitation->id,
                            ]);
                            $logFn('TwiML URL: '.$twimlUrl);

                            $statusCallbackUrl = rtrim(config('app.url'), '/').'/calling-status.php?invitation_id='.$invitation->id;
                            $call = $client->calls->create(
                                $searchedPhone,
                                $from,
                                [
                                    'url' => $twimlUrl,
                                    'statusCallback' => $statusCallbackUrl,
                                    'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                                ]
                            );

                            $logFn('Twilio call created, SID='.$call->sid);
                            $guestNameEscaped = htmlspecialchars($guest->name, ENT_QUOTES, 'UTF-8');
                            $message .= "<br>השיחה יצאה בהצלחה לאורח: <strong>{$guestNameEscaped}</strong><br>Call SID: ".htmlspecialchars((string) $call->sid, ENT_QUOTES, 'UTF-8');
                            $messageType = 'success';
                            $lastCallSid = $call->sid;
                        } catch (\Exception $e) {
                            $message = 'שגיאה ביצירת השיחה: '.$e->getMessage();
                            $messageType = 'error';
                            $logFn('Twilio call error: '.$e->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $message = 'שגיאה: '.$e->getMessage();
            $messageType = 'error';
            $logFn('Exception: '.$e->getMessage());
        }

        if ($isStream) {
            $streamLine([
                'type' => 'done',
                'success' => $messageType === 'success',
                'message' => $message,
                'messageType' => $messageType,
                'showNewGuestForm' => $showNewGuestForm,
                'searchedPhone' => $searchedPhone,
                'callSid' => $lastCallSid,
            ]);
            exit;
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
        .terminal-log-wrap { display: none; }
        .terminal-log-wrap.visible { display: block; }
        button:disabled { opacity: 0.7; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="card">
        <h1>📞 שיחות RSVP</h1>
        <p class="subtitle">הזן מספר טלפון של אורח כדי להתקשר ולבקש אישור הגעה</p>

        <div id="call-msg" class="msg" role="alert" style="<?= $message ? '' : 'display:none;' ?>" data-type="<?= htmlspecialchars($messageType) ?>"><?= $message ?></div>

        <form id="calling-form" method="post" action="calling.php">
            <div class="form-group">
                <label for="number">מספר טלפון לאישור הגעה</label>
                <input type="text" id="number" name="number" class="ltr" placeholder="050-1234567 או +972501234567" value="<?= htmlspecialchars($searchedPhone) ?>" <?= $showNewGuestForm ? 'readonly' : 'required autofocus' ?>>
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
                <button type="submit" id="submit-btn">➕ הוסף אורח וחייג עכשיו</button>
                <button type="button" onclick="window.location.href='calling.php'" style="background: transparent; border: 1px solid #475569; margin-top: 10px;">ביטול חיפוש</button>
            <?php } else { ?>
                <button type="submit" id="submit-btn">📲 חייג עכשיו</button>
            <?php } ?>
        </form>

        <div id="terminal-log-wrap" class="terminal-log terminal-log-wrap <?= ! empty($log) ? 'visible' : '' ?>">
            <div class="terminal-log-title">TERMINAL LOG <span id="terminal-status"></span></div>
            <pre id="terminal-log-pre" class="terminal-log-pre"><?php
                if (! empty($log)) {
                    echo '$ '.htmlspecialchars(implode("\n$ ", $log), ENT_QUOTES, 'UTF-8');
                }
?></pre>
            <div id="call-log-wrap" class="terminal-log-wrap" style="margin-top:1rem; border-top:1px solid #1e293b; padding-top:0.75rem;">
                <div class="terminal-log-title">לוג שיחה <span id="call-log-status"></span></div>
                <pre id="call-log-pre" class="terminal-log-pre" style="max-height:180px;"></pre>
            </div>
        </div>
    </div>
    <script>
(function() {
    const form = document.getElementById('calling-form');
    const callMsg = document.getElementById('call-msg');
    const terminalWrap = document.getElementById('terminal-log-wrap');
    const terminalPre = document.getElementById('terminal-log-pre');
    const terminalStatus = document.getElementById('terminal-status');
    const submitBtn = document.getElementById('submit-btn');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (submitBtn) submitBtn.disabled = true;
        terminalWrap.classList.add('visible');
        terminalPre.textContent = '';
        terminalStatus.textContent = '… מתחבר';
        callMsg.style.display = 'none';

        const formData = new FormData(form);
        const url = form.action + (form.action.includes('?') ? '&' : '?') + 'stream=1';
        let buffer = '';

        try {
            const res = await fetch(url, { method: 'POST', body: formData });
            if (!res.ok) throw new Error('Network ' + res.status);
            if (!res.body) throw new Error('No stream');
            const reader = res.body.getReader();
            const dec = new TextDecoder();
            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += dec.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';
                for (const line of lines) {
                    const t = line.trim();
                    if (!t) continue;
                    try {
                        const data = JSON.parse(t);
                        if (data.type === 'log') {
                            terminalPre.textContent += (terminalPre.textContent ? '\n' : '') + '$ ' + data.msg;
                            terminalPre.scrollTop = terminalPre.scrollHeight;
                            if (terminalStatus.textContent === '… מתחבר') terminalStatus.textContent = '';
                        } else if (data.type === 'done') {
                            terminalStatus.textContent = data.success ? 'השיחה יצאה' : '✗ שגיאה';
                            callMsg.innerHTML = data.message || '';
                            callMsg.className = 'msg ' + (data.messageType || '');
                            callMsg.style.display = data.message ? 'block' : 'none';
                            if (data.showNewGuestForm) window.location.href = 'calling.php?show_new_guest=1&number=' + encodeURIComponent(data.searchedPhone || '');
                            if (data.callSid) startCallLogPoll(data.callSid);
                        }
                    } catch (_) {}
                }
            }
            if (buffer.trim()) {
                try {
                    const data = JSON.parse(buffer.trim());
                    if (data.type === 'done') {
                        terminalStatus.textContent = data.success ? 'השיחה יצאה' : '✗ שגיאה';
                        callMsg.innerHTML = data.message || '';
                        callMsg.className = 'msg ' + (data.messageType || '');
                        callMsg.style.display = data.message ? 'block' : 'none';
                        if (data.showNewGuestForm) window.location.href = 'calling.php?show_new_guest=1&number=' + encodeURIComponent(data.searchedPhone || '');
                        if (data.callSid) startCallLogPoll(data.callSid);
                    }
                } catch (_) {}
            }
        } catch (err) {
            terminalStatus.textContent = '✗ שגיאה';
            terminalPre.textContent += (terminalPre.textContent ? '\n' : '') + '$ Error: ' + err.message;
            callMsg.textContent = 'שגיאה: ' + err.message;
            callMsg.className = 'msg error';
            callMsg.style.display = 'block';
        }
        if (submitBtn) submitBtn.disabled = false;
    });

    const callLogStatusEl = document.getElementById('call-log-status');
    const callLogPreEl = document.getElementById('call-log-pre');
    const callLogWrapEl = document.getElementById('call-log-wrap');
    let callLogPollTimer = null;

    function statusLabel(s) {
        if (!s) return '';
        var map = { 'queued': 'בתור', 'ringing': 'מצלצל', 'in-progress': 'נענתה', 'completed': 'הושלמה', 'busy': 'תפוס', 'failed': 'נכשל', 'no-answer': 'לא נענה', 'canceled': 'בוטל' };
        return map[s] || s;
    }

    function startCallLogPoll(callSid) {
        if (callLogWrapEl) callLogWrapEl.classList.add('visible');
        if (callLogStatusEl) callLogStatusEl.textContent = '… טוען';
        function poll() {
            fetch('calling-log.php?call_sid=' + encodeURIComponent(callSid))
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (callLogStatusEl) callLogStatusEl.textContent = d.status ? statusLabel(d.status) : '';
                    var lines = d.lines || [];
                    if (lines.length > 0 || d.status) {
                        var parts = [];
                        if (d.status) parts.push('סטטוס: ' + statusLabel(d.status));
                        lines.forEach(function(l) {
                            parts.push((l.role === 'user' ? 'אורח: ' : 'בוט: ') + (l.text || '').trim());
                        });
                        if (callLogPreEl) callLogPreEl.textContent = parts.join('\n');
                        callLogPreEl.scrollTop = callLogPreEl.scrollHeight;
                    }
                    if (d.status === 'completed' || d.status === 'failed' || d.status === 'busy' || d.status === 'no-answer' || d.status === 'canceled') {
                        if (terminalStatus) terminalStatus.textContent = statusLabel(d.status);
                        if (callLogPollTimer) clearInterval(callLogPollTimer);
                        callLogPollTimer = null;
                        return;
                    }
                })
                .catch(function() {});
        }
        poll();
        callLogPollTimer = setInterval(poll, 2000);
    }
})();
    </script>
</body>
</html>
