<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Mail\Mailables\Attachment;

class WelcomeOrganizer extends MjmlMailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(
        public Organization $organization,
        public User $user,
        public ?string $pdfUrl = null
    ) {
        $this->subject(__('Welcome to Kalfa - :organization', ['organization' => $this->organization->name]));
    }

    /**
     * Get the MJML view for the message.
     */
    public function mjmlView(): string
    {
        return 'emails.welcome-organizer.mjml';
    }

    /**
     * Get the data for the MJML view.
     *
     * @return array<string, mixed>
     */
    public function mjmlData(): array
    {
        return [
            'organization' => $this->organization,
            'user' => $this->user,
            'pdfUrl' => $this->pdfUrl,
        ];
    }

    protected function mailLanguage(): string
    {
        return 'he';
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
