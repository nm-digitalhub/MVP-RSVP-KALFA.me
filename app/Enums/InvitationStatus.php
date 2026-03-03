<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Opened = 'opened';
    case Responded = 'responded';
    case Expired = 'expired';
}
