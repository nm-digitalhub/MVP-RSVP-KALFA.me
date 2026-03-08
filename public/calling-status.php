<?php

declare(strict_types=1);

/**
 * Twilio call status callback.
 * Receives POST with CallSid, CallStatus; stores in cache for calling.php log.
 * When status is no-answer, sends WhatsApp with RSVP link to the guest (invitation_id in query).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$callSid = trim((string) ($_POST['CallSid'] ?? ''));
$callStatus = trim((string) ($_POST['CallStatus'] ?? ''));
$callDuration = isset($_POST['CallDuration']) ? (int) $_POST['CallDuration'] : null;

header('Content-Type: text/plain; charset=utf-8');

if ($callSid === '' || $callStatus === '') {
    echo 'OK';
    exit;
}

$cacheKey = 'call_log:'.$callSid;
$existing = Illuminate\Support\Facades\Cache::get($cacheKey, ['status' => null, 'lines' => [], 'updated_at' => null]);
$existing['status'] = $callStatus;
$existing['updated_at'] = now()->toIso8601String();

$endMessages = [
    'completed' => 'האורח ניתק',
    'no-answer' => 'לא נענה',
    'canceled' => 'השיחה בוטלה',
    'busy' => 'המספר תפוס',
    'failed' => 'השיחה נכשלה',
];
if (isset($endMessages[$callStatus])) {
    $existing['lines'] = array_merge($existing['lines'] ?? [], [
        ['role' => 'bot', 'text' => $endMessages[$callStatus], 'at' => now()->toIso8601String()],
    ]);
}

Illuminate\Support\Facades\Cache::put($cacheKey, $existing, 3600);

$invitationId = isset($_GET['invitation_id']) ? (int) $_GET['invitation_id'] : (int) ($_REQUEST['invitation_id'] ?? 0);

$logLine = date('c').' call_sid='.$callSid.' status='.$callStatus.' duration='.($callDuration ?? 'n/a').' invitation_id='.$invitationId."\n";
@file_put_contents(__DIR__.'/../storage/logs/calling-status.log', $logLine, FILE_APPEND | LOCK_EX);

\Illuminate\Support\Facades\Log::info('calling_status_callback', [
    'call_sid' => $callSid,
    'call_status' => $callStatus,
    'call_duration' => $callDuration,
    'invitation_id_param' => $invitationId,
]);

$sendWhatsApp = in_array($callStatus, ['no-answer', 'canceled'], true)
    || ($callStatus === 'completed' && $callDuration !== null && $callDuration <= 5);
if ($sendWhatsApp && $invitationId > 0) {
    $invitation = \App\Models\Invitation::find($invitationId);
    if ($invitation) {
        try {
            $result = $app->make(\App\Services\WhatsAppRsvpService::class)->sendRsvpLink($invitation);
            if ($result['success']) {
                \Illuminate\Support\Facades\Log::info('WhatsApp RSVP sent after call not answered', [
                    'invitation_id' => $invitationId,
                    'call_sid' => $callSid,
                    'status' => $callStatus,
                    'message_sid' => $result['sid'] ?? null,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('WhatsApp RSVP on no-answer/canceled failed', [
                    'invitation_id' => $invitationId,
                    'call_sid' => $callSid,
                    'error' => $result['error'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WhatsApp RSVP on no-answer exception', [
                'invitation_id' => $invitationId,
                'call_sid' => $callSid,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

echo 'OK';
