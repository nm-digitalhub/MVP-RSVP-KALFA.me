<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponTargetType: string
{
    case Global = 'global';
    case Subscription = 'subscription';
    case Plan = 'plan';
    case EventBilling = 'event_billing';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'כל הרכישות',
            self::Subscription => 'מנויים בלבד',
            self::Plan => 'תוכניות ספציפיות',
            self::EventBilling => 'אירועים בלבד',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Global => 'חל על כל סוגי הרכישות',
            self::Subscription => 'חל רק על רכישת מנויים',
            self::Plan => 'חל על תוכניות שנבחרו בלבד',
            self::EventBilling => 'חל רק על חיוב אירועים',
        };
    }
}
