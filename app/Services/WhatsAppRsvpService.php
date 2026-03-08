<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invitation;
use Twilio\Rest\Client as TwilioClient;

class WhatsAppRsvpService
{
    public function __construct(
        private readonly TwilioClient $twilio
    ) {}

    /**
     * Build the public RSVP page URL for an invitation.
     */
    public function rsvpUrl(Invitation $invitation): string
    {
        $base = rtrim(config('app.url'), '/');

        return $base.'/rsvp/'.$invitation->slug;
    }

    /**
     * Normalize phone to E.164 for WhatsApp (e.g. 0501234567 -> +972501234567).
     */
    public static function phoneToE164(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', trim($phone));
        $len = strlen($digits);

        if ($len === 10 && str_starts_with($digits, '0')) {
            return '+972'.substr($digits, 1);
        }
        if ($len === 9 && str_starts_with($digits, '5')) {
            return '+972'.$digits;
        }
        if ($len >= 11 && $len <= 12 && str_starts_with($digits, '972')) {
            return '+'.$digits;
        }
        if ($len >= 10 && $len <= 15 && preg_match('/^[1-9]\d{9,14}$/', $digits)) {
            return '+'.$digits;
        }
        if ($len >= 9 && $len <= 15 && preg_match('/^[1-9]\d{8,14}$/', $digits)) {
            return '+'.$digits;
        }

        return null;
    }

    /**
     * Send WhatsApp message with RSVP link to the invitation's guest.
     *
     * @return array{ sid: string, success: bool, error?: string }
     */
    public function sendRsvpLink(Invitation $invitation): array
    {
        $invitation->loadMissing(['guest', 'event']);
        $guest = $invitation->guest;

        if (! $guest || ! $guest->phone) {
            return ['sid' => '', 'success' => false, 'error' => 'Guest or phone missing'];
        }

        $toE164 = self::phoneToE164($guest->phone);
        if (! $toE164 || ! preg_match('/^\+[1-9]\d{8,14}$/', $toE164)) {
            return ['sid' => '', 'success' => false, 'error' => 'Invalid guest phone for WhatsApp'];
        }

        $url = $this->rsvpUrl($invitation);
        $eventName = $invitation->event?->name ?? 'האירוע';
        $body = "שלום {$guest->name}!\n\nהוזמנת ל{$eventName}. לאשר הגעה לחץ כאן:\n{$url}";

        $from = $this->whatsappFrom();
        if (! $from) {
            return ['sid' => '', 'success' => false, 'error' => 'WhatsApp sender not configured (TWILIO_WHATSAPP_FROM or TWILIO_NUMBER)'];
        }

        try {
            $message = $this->twilio->messages->create(
                'whatsapp:'.$toE164,
                [
                    'from' => $from,
                    'body' => $body,
                ]
            );

            $sid = $message->sid;
            $failure = $this->checkMessageDeliveryFailure($sid);
            if ($failure !== null) {
                return ['sid' => $sid, 'success' => false, 'error' => $failure];
            }

            return ['sid' => $sid, 'success' => true];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $translated = self::translateTwilioErrorToHebrew($error);

            return ['sid' => '', 'success' => false, 'error' => $translated];
        }
    }

    /**
     * After sending, Twilio may report failure asynchronously (e.g. 63015 = user not in Sandbox).
     * Poll once after a short delay and return a user-friendly error if failed.
     */
    private function checkMessageDeliveryFailure(string $sid): ?string
    {
        sleep(2);
        try {
            $msg = $this->twilio->messages($sid)->fetch();
            if (($msg->status ?? '') !== 'failed') {
                return null;
            }
            $code = $msg->errorCode ?? 0;

            return self::errorCodeToHebrew((int) $code);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Translate Twilio WhatsApp error codes to Hebrew for logs and support.
     */
    public static function errorCodeToHebrew(int $code): string
    {
        return match ($code) {
            63015 => 'הנמען לא הצטרף ל-WhatsApp Sandbox. יש לשלוח מהטלפון של הנמען למספר +14155238886 את ההודעה: join ואז את המילה שמופיעה ב-Twilio Console (Messaging → Try WhatsApp).',
            63016 => 'הודעת WhatsApp נחסמה על ידי הנמען.',
            63017 => 'מספר לא רשום ב-WhatsApp.',
            21211 => 'מספר הנמען לא תקין.',
            default => "שגיאת Twilio (קוד {$code}).",
        };
    }

    /**
     * If the exception message contains a known error code, return Hebrew explanation.
     */
    private static function translateTwilioErrorToHebrew(string $message): string
    {
        if (str_contains($message, '63015')) {
            return self::errorCodeToHebrew(63015);
        }
        if (str_contains($message, '63016')) {
            return self::errorCodeToHebrew(63016);
        }
        if (str_contains($message, '63017')) {
            return self::errorCodeToHebrew(63017);
        }
        if (str_contains($message, '21211')) {
            return self::errorCodeToHebrew(21211);
        }
        if (str_contains($message, 'Could not find a Channel')) {
            return 'מספר השולח (From) לא מוגדר ל-WhatsApp. הגדר TWILIO_WHATSAPP_FROM למספר Sandbox (+14155238886) או למספר WhatsApp Business.';
        }

        return $message;
    }

    /**
     * WhatsApp "from" address (whatsapp:+...). Prefer TWILIO_WHATSAPP_FROM, else whatsapp:TWILIO_NUMBER.
     */
    private function whatsappFrom(): ?string
    {
        $from = config('services.twilio.whatsapp_from');
        if ($from && $from !== '') {
            return str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from;
        }
        $number = config('services.twilio.number');
        if ($number && $number !== '') {
            $e164 = str_starts_with($number, '+') ? $number : '+'.$number;

            return 'whatsapp:'.$e164;
        }

        return null;
    }
}
