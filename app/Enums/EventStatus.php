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

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::PendingPayment => __('Pending Payment'),
            self::Active => __('Active'),
            self::Locked => __('Locked'),
            self::Archived => __('Archived'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::Draft, self::Archived => 'neutral',
            self::PendingPayment => 'warning',
            self::Active => 'success',
            self::Locked => 'info',
            self::Cancelled => 'danger',
        };
    }
}
