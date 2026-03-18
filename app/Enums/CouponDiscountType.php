<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponDiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case TrialExtension = 'trial_extension';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'אחוז הנחה',
            self::Fixed => 'סכום קבוע',
            self::TrialExtension => 'הארכת Trial',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Percentage => 'הפחתת אחוז מהמחיר המקורי',
            self::Fixed => 'הפחתת סכום קבוע בשקלים',
            self::TrialExtension => 'מוסיף ימי ניסיון לחשבון',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Percentage => '%',
            self::Fixed => '₪',
            self::TrialExtension => 'ימים',
        };
    }
}
