<?php

declare(strict_types=1);

namespace App\Http\Controllers\Twilio;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Services\CallingService;
use App\Services\WhatsAppRsvpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class CallingController extends Controller
{
    public function __construct(
        private readonly CallingService $callingService,
        private readonly WhatsAppRsvpService $whatsAppRsvp
    ) {}

    /**
     * Show the calling interface.
     */
    public function index(Request $request): View
    {
        return view('twilio.calling', [
            'showNewGuestForm' => $request->boolean('show_new_guest'),
            'searchedPhone' => $request->query('number', ''),
        ]);
    }

    /**
     * Initiate a call.
     */
    public function call(Request $request): \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse|string
    {
        if ($request->boolean('stream')) {
            return $this->handleStreamedCall($request);
        }

        $result = $this->processCallLogic($request);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get call log for UI polling (status + lines from cache).
     */
    public function getLogs(Request $request): JsonResponse
    {
        $callSid = trim((string) $request->query('call_sid', ''));
        if ($callSid === '') {
            return response()->json(['status' => null, 'lines' => []], 200);
        }

        $cacheKey = 'call_log:'.$callSid;
        $data = Cache::get($cacheKey, ['status' => null, 'lines' => [], 'updated_at' => null]);

        return response()->json([
            'status' => $data['status'] ?? null,
            'lines' => $data['lines'] ?? [],
        ], 200);
    }

    /**
     * Append log line(s) from Node.js media server (secured by CALL_LOG_SECRET).
     */
    public function appendLog(Request $request): JsonResponse
    {
        $secret = config('services.twilio.call_log_secret', '');
        if ($secret !== '' && $request->input('secret') !== $secret && $request->header('X-Call-Log-Secret') !== $secret && $request->input('key') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $callSid = trim((string) $request->input('call_sid', ''));
        if ($callSid === '') {
            return response()->json(['error' => 'call_sid required'], 422);
        }

        $cacheKey = 'call_log:'.$callSid;
        $existing = Cache::get($cacheKey, ['status' => null, 'lines' => [], 'updated_at' => null]);

        // Support both array of lines and single line entry
        $lines = $request->input('lines', []);
        if (! is_array($lines)) {
            $lines = [];
        }

        if ($request->has('text')) {
            $lines[] = [
                'role' => $request->input('role', 'bot'),
                'text' => $request->input('text'),
            ];
        }

        if (empty($lines)) {
            return response()->json(['ok' => true, 'message' => 'no lines to append'], 200);
        }

        foreach ($lines as $line) {
            $role = $line['role'] ?? 'bot';
            $text = $line['text'] ?? '';
            if (trim((string) $text) === '') {
                continue;
            }
            $existing['lines'][] = [
                'role' => $role,
                'text' => $text,
                'at' => now()->toIso8601String(),
            ];
        }
        $existing['updated_at'] = now()->toIso8601String();
        Cache::put($cacheKey, $existing, 3600);

        return response()->json(['ok' => true], 200);
    }

    /**
     * Handle Twilio status callback.
     */
    public function statusCallback(Request $request): string
    {
        $callSid = (string) $request->input('CallSid', '');
        $callStatus = (string) $request->input('CallStatus', '');
        $callDuration = $request->input('CallDuration') !== null ? (int) $request->input('CallDuration') : null;
        $invitationId = (int) $request->input('invitation_id', 0);

        if ($callSid === '' || $callStatus === '') {
            return 'OK';
        }

        $this->updateCallLog($callSid, $callStatus);

        Log::info('calling_status_callback', [
            'call_sid' => $callSid,
            'call_status' => $callStatus,
            'call_duration' => $callDuration,
            'invitation_id' => $invitationId,
        ]);

        // WhatsApp Fallback logic
        $shouldSendWhatsApp = in_array($callStatus, ['no-answer', 'canceled'], true)
            || ($callStatus === 'completed' && $callDuration !== null && $callDuration <= 5);

        if ($shouldSendWhatsApp && $invitationId > 0) {
            $this->triggerWhatsAppFallback($invitationId);
        }

        return 'OK';
    }

    private function processCallLogic(Request $request, ?callable $streamLog = null): array
    {
        $rawNumber = trim((string) $request->input('number', ''));
        if ($rawNumber === '') {
            return $this->errorResponse('חסר מספר טלפון.', $streamLog);
        }

        $normalized = $this->callingService->normalizePhoneNumber($rawNumber);
        if (! preg_match('/^\+[1-9]\d{8,14}$/', $normalized)) {
            return $this->errorResponse('מספר טלפון לא תקין.', $streamLog);
        }

        $guest = $this->callingService->findGuestByPhone($normalized);

        // Handle guest creation if requested
        if (! $guest && $request->input('action') === 'create_guest') {
            $guest = $this->createGuestFromRequest($request, $normalized);
            if (! $guest) {
                return $this->errorResponse('חובה להזין שם מלא.', $streamLog, ['showNewGuestForm' => true]);
            }
        }

        if (! $guest) {
            return $this->errorResponse('המספר לא נמצא. הזן פרטים להוספה.', $streamLog, [
                'showNewGuestForm' => true,
                'searchedPhone' => $normalized,
            ]);
        }

        $invitation = $this->callingService->ensureInvitation($guest);

        try {
            $callSid = $this->callingService->initiateCall($guest, $invitation);
            $msg = "השיחה יצאה בהצלחה לאורח: <strong>{$guest->name}</strong>";

            $res = ['success' => true, 'message' => $msg, 'callSid' => $callSid];
            if ($streamLog) {
                $streamLog(['type' => 'done', ...$res]);
            }

            return $res;
        } catch (\Throwable $e) {
            return $this->errorResponse('שגיאה ביצירת השיחה: '.$e->getMessage(), $streamLog);
        }
    }

    private function createGuestFromRequest(Request $request, string $phone): ?Guest
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return null;
        }

        $event = Event::where('event_date', '>=', now()->startOfDay())->orderBy('event_date')->first();
        if (! $event) {
            return null;
        }

        return Guest::create([
            'event_id' => $event->id,
            'name' => $name,
            'phone' => $phone,
            'email' => $request->input('email'),
            'group_name' => $request->input('group_name'),
            'sort_order' => 0,
        ]);
    }

    private function errorResponse(string $msg, ?callable $streamLog, array $extra = []): array
    {
        $res = ['success' => false, 'message' => $msg, ...$extra];
        if ($streamLog) {
            $streamLog(['type' => 'done', ...$res]);
        }

        return $res;
    }

    private function updateCallLog(string $callSid, string $status): void
    {
        $cacheKey = 'call_log:'.$callSid;
        $existing = Cache::get($cacheKey, ['status' => null, 'lines' => [], 'updated_at' => null]);

        $endMessages = [
            'completed' => 'האורח ניתק',
            'no-answer' => 'לא נענה',
            'canceled' => 'השיחה בוטלה',
            'busy' => 'המספר תפוס',
            'failed' => 'השיחה נכשלה',
        ];

        $existing['status'] = $status;
        $existing['updated_at'] = now()->toIso8601String();

        if (isset($endMessages[$status])) {
            $existing['lines'][] = [
                'role' => 'bot',
                'text' => $endMessages[$status],
                'at' => now()->toIso8601String(),
            ];
        }

        Cache::put($cacheKey, $existing, 3600);
    }

    private function triggerWhatsAppFallback(int $invitationId): void
    {
        $invitation = Invitation::find($invitationId);
        if ($invitation) {
            try {
                $this->whatsAppRsvp->sendRsvpLink($invitation);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp fallback failed', ['error' => $e->getMessage()]);
            }
        }
    }

    private function handleStreamedCall(Request $request): mixed
    {
        return response()->stream(function () use ($request) {
            $streamLine = function (array $payload) {
                echo json_encode($payload, JSON_UNESCAPED_UNICODE)."\n";
                if (function_exists('ob_flush')) {
                    ob_flush();
                }
                flush();
            };
            $this->processCallLogic($request, $streamLine);
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
