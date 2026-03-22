<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Support\Carbon;

class TrialExpiringReminder extends MjmlMailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $ownerName,
        public int $daysRemaining,
        public string $organizationName,
        public string $planName,
        public Carbon $trialEndsAt,
        public string $selectPlanUrl,
        public string $mailLocale = 'he'
    ) {
        $this->subject($this->mailLocale === 'he' ? 'תזכורת: תקופת הניסיון שלך עומדת להסתיים' : 'Reminder: Your trial is ending soon');
    }

    /**
     * Get the MJML view for the message.
     */
    public function mjmlView(): string
    {
        return $this->mailLocale === 'he'
            ? 'emails.trial-expiring-reminder.mjml'
            : 'emails.trial-expiring-reminder-en.mjml';
    }

    /**
     * Get the data for the MJML view.
     */
    public function mjmlData(): array
    {
        return [
            'ownerName' => $this->ownerName,
            'daysRemaining' => $this->daysRemaining,
            'organizationName' => $this->organizationName,
            'planName' => $this->planName,
            'trialEndsAt' => $this->trialEndsAt,
            'selectPlanUrl' => $this->selectPlanUrl,
        ];
    }

    protected function mailLanguage(): string
    {
        return $this->mailLocale;
    }
}
