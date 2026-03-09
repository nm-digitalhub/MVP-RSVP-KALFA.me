<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductPriceBillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Usage = 'usage';
}
