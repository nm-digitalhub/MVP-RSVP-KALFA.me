<?php

/**
 * Twilio TwiML endpoint
 * Called when the call connects.
 * Opens Media Stream to Node server.
 */

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use App\Models\Event;
use App\Models\Guest;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$guestId      = (int) ($_GET['guest_id'] ?? 0);
$eventId      = (int) ($_GET['event_id'] ?? 0);
$invitationId = (int) ($_GET['invitation_id'] ?? 0);

$guest = Guest::find($guestId);
$event = Event::find($eventId);

header('Content-Type: text/xml; charset=utf-8');

// אם חסר אורח / אירוע / הזמנה – מחזירים הודעת שגיאה בסיסית למתקשר
if (! $guest || ! $event || $invitationId <= 0) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say language="he-IL">אירעה שגיאה. להתראות.</Say></Response>';
    exit;
}

/**
 * כתובת שרת Node (Media WebSocket)
 * מומלץ להגדיר ב-ENV: RSVP_NODE_WS_URL=wss://node.kalfa.me/media
 */
$nodeWsUrl = env('RSVP_NODE_WS_URL', 'wss://node.kalfa.me/media');

$streamUrl = $nodeWsUrl.'?'.http_build_query([
    'guest_id'      => $guestId,
    'event_id'      => $eventId,
    'invitation_id' => $invitationId,
    'guest_name'    => $guest->name,
]);

$escapedStreamUrl = htmlspecialchars($streamUrl, ENT_XML1, 'UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Connect>
        <Stream url="<?= $escapedStreamUrl ?>" />
    </Connect>
</Response>
