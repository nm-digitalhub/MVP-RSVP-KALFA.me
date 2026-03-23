<?php

declare(strict_types=1);

namespace App\Enums;

enum UsagePolicyDecision: string
{
    case Allowed = 'allowed';
    case AllowedWithOverage = 'allowed_with_overage';
    case Blocked = 'blocked';

    public function isBlocked(): bool
    {
        return $this === self::Blocked;
    }

    public function isAllowed(): bool
    {
        return $this === self::Allowed;
    }

    public function isAllowedWithOverage(): bool
    {
        return $this === self::AllowedWithOverage;
    }

    public function requiresOveragePayment(): bool
    {
        return $this === self::AllowedWithOverage;
    }
}
