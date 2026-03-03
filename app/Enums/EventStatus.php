<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Locked = 'locked';
    case Archived = 'archived';
    case Cancelled = 'cancelled';
}
