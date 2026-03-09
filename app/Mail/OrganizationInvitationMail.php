<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrganizationInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You have been invited to join :organization', ['organization' => $this->invitation->organization->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.organization-invitation',
            with: [
                'acceptUrl' => route('invitations.accept', ['token' => $this->invitation->token]),
            ],
        );
    }
}
