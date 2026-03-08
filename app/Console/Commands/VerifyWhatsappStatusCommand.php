<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\VerifyWhatsAppService;
use Illuminate\Console\Command;

class VerifyWhatsappStatusCommand extends Command
{
    protected $signature = 'verify:whatsapp-status
                            {--send= : Send a test OTP to this E.164 number (e.g. +972501234567)}
                            {--channel=whatsapp : Channel: whatsapp or sms}';

    protected $description = 'Show Verify WhatsApp configuration status and optionally send a test OTP';

    public function handle(VerifyWhatsAppService $verify): int
    {
        $serviceSid = $verify->getVerifyServiceSid();
        if (! $serviceSid) {
            $this->error('TWILIO_VERIFY_SID is not set in .env. Create a Verify Service and set TWILIO_VERIFY_SID=VA...');
            $this->line('  Create: twilio api:verify:v2:services:create --friendly-name "Kalfa OTP"');

            return self::FAILURE;
        }

        $this->info('Verify Service: '.$serviceSid);

        $configured = $verify->isWhatsAppConfigured();
        if ($configured) {
            $this->info('WhatsApp: configured (Messaging Service linked).');
        } else {
            $this->warn('WhatsApp: not configured.');
            $this->newLine();
            $this->line('To enable OTP via WhatsApp:');
            $this->line('  1. Activate Sandbox: https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn');
            $this->line('  2. Send "join <code>" from your WhatsApp to the Sandbox number.');
            $this->line('  3. Messaging → Services → "Kalfa Verify WhatsApp" → Sender Pool → Add Sandbox.');
            $this->line('  4. Verify → Services → Kalfa OTP → WhatsApp tab → Select "Kalfa Verify WhatsApp".');
            $this->newLine();
            $this->line('See docs/verify-whatsapp-setup.md for full steps.');
        }

        $to = $this->option('send');
        if ($to !== null && $to !== '') {
            $channel = $this->option('channel') ?: 'whatsapp';
            if (! in_array($channel, ['whatsapp', 'sms'], true)) {
                $this->error('--channel must be whatsapp or sms');

                return self::FAILURE;
            }
            if ($channel === 'whatsapp' && ! $configured) {
                $this->warn('WhatsApp is not configured; sending via SMS instead.');
                $channel = 'sms';
            }
            try {
                $result = $verify->sendVerification($to, $channel);
                $this->info("OTP sent to {$to} via {$channel}. SID: {$result['sid']}, status: {$result['status']}");
            } catch (\Throwable $e) {
                $this->error('Failed to send: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
