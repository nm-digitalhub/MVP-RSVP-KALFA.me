<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductPriceBillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Usage = 'usage';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => __('Monthly'),
            self::Yearly => __('Yearly'),
            self::Usage => __('Usage'),
        };
    }
}
