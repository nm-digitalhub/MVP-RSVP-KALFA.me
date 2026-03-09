<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email? : Optional recipient (default: MAIL_FROM_ADDRESS)}';

    protected $description = 'Send a test email via configured SMTP (for debugging delivery)';

    public function handle(): int
    {
        $to = $this->argument('email') ?: config('mail.from.address');
        $this->info("Sending test email to: {$to}");
        $this->info('Mailer: '.config('mail.default').' | Host: '.config('mail.mailers.smtp.host'));

        try {
            $sentMessage = Mail::raw(
                'Test from Kalfa at '.now()->toIso8601String().'. If you receive this, SMTP delivery works.',
                function ($m) use ($to): void {
                    $m->to($to)->subject('Kalfa SMTP test '.now()->format('Y-m-d H:i:s'));
                }
            );
            $this->info('Mail::send completed without exception. Check inbox (and spam).');
            if ($sentMessage) {
                $debug = $sentMessage->getSymfonySentMessage()->getDebug();
                if ($debug !== '') {
                    $this->newLine();
                    $this->line('<comment>SMTP transcript:</comment>');
                    $this->line($debug);
                }
            }
        } catch (\Throwable $e) {
            $this->error('Exception: '.$e->getMessage());
            $this->line($e->getTraceAsString());
            if (method_exists($e, 'getDebug') && $e->getDebug() !== '') {
                $this->line('<comment>SMTP debug:</comment>');
                $this->line($e->getDebug());
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
