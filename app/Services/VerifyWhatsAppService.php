<?php

declare(strict_types=1);

namespace App\Services;

use Twilio\Rest\Client as TwilioClient;

class VerifyWhatsAppService
{
    private const CHANNEL_WHATSAPP = 'whatsapp';

    private const CHANNEL_SMS = 'sms';

    public function __construct(
        private readonly TwilioClient $twilio
    ) {}

    public function getVerifyServiceSid(): ?string
    {
        $sid = config('services.twilio.verify_sid');

        return $sid && $sid !== '' ? $sid : null;
    }

    /**
     * Send OTP to the given E.164 number via WhatsApp or SMS.
     *
     * @param  string  $to  E.164 phone number (e.g. +972501234567)
     * @param  'whatsapp'|'sms'  $channel
     * @return array{ sid: string, status: string }
     *
     * @throws \RuntimeException If Verify Service SID is not configured
     * @throws \Twilio\Exceptions\TwilioException On API errors
     */
    public function sendVerification(string $to, string $channel = self::CHANNEL_WHATSAPP): array
    {
        $serviceSid = $this->getVerifyServiceSid();
        if (! $serviceSid) {
            throw new \RuntimeException('TWILIO_VERIFY_SID is not set. Create a Verify Service and set it in .env');
        }

        $verification = $this->twilio->verify->v2
            ->services($serviceSid)
            ->verifications
            ->create($to, $channel);

        return [
            'sid' => $verification->sid,
            'status' => $verification->status,
        ];
    }

    /**
     * Check the OTP code entered by the user.
     *
     * @param  string  $to  E.164 phone number (same as used in sendVerification)
     * @param  string  $code  The code entered by the user
     * @return array{ valid: bool, status: string }
     *
     * @throws \RuntimeException If Verify Service SID is not configured
     * @throws \Twilio\Exceptions\TwilioException On API errors
     */
    public function checkVerification(string $to, string $code): array
    {
        $serviceSid = $this->getVerifyServiceSid();
        if (! $serviceSid) {
            throw new \RuntimeException('TWILIO_VERIFY_SID is not set.');
        }

        $check = $this->twilio->verify->v2
            ->services($serviceSid)
            ->verificationChecks
            ->create([
                'to' => $to,
                'code' => $code,
            ]);

        return [
            'valid' => $check->status === 'approved',
            'status' => $check->status,
        ];
    }

    /**
     * Whether the Verify Service has WhatsApp configured (Messaging Service with WhatsApp Sender).
     */
    public function isWhatsAppConfigured(): bool
    {
        $serviceSid = $this->getVerifyServiceSid();
        if (! $serviceSid) {
            return false;
        }

        try {
            $service = $this->twilio->verify->v2->services($serviceSid)->fetch();
            $msgServiceSid = $service->whatsapp['msg_service_sid'] ?? null;

            return $msgServiceSid !== null && $msgServiceSid !== '';
        } catch (\Throwable) {
            return false;
        }
    }
}
