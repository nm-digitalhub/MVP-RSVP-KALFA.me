<?php

declare(strict_types=1);

namespace App\Enums;

enum UsagePolicyDecision: string
{
    case Allowed = 'allowed';
    case AllowedWithOverage = 'allowed_with_overage';
    case Blocked = 'blocked';
}
