<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OrganizationInvitation;

class OrganizationInvitationMail extends MjmlMailable
{
    public function __construct(
        public OrganizationInvitation $invitation
    ) {
        $this->subject(__('You have been invited to join :organization', ['organization' => $this->invitation->organization->name]));
    }

    public function mjmlView(): string
    {
        return 'emails.organization-invitation.mjml';
    }

    /**
     * @return array<string, mixed>
     */
    public function mjmlData(): array
    {
        return [
            'invitation' => $this->invitation,
            'acceptUrl' => route('invitations.accept', ['token' => $this->invitation->token]),
        ];
    }

    protected function mailLanguage(): string
    {
        return 'he';
    }
}
