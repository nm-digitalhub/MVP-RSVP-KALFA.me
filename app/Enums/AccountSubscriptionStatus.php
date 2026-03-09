<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountSubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
}
