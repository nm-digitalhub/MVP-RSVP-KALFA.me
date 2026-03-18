<?php

declare(strict_types=1);

namespace App\Enums;

enum CreditSource: string
{
    case Manual = 'manual';
    case Coupon = 'coupon';
    case Refund = 'refund';
    case CheckoutApplied = 'checkout_applied';
    case SubscriptionCycle = 'subscription_cycle';
    case Adjustment = 'adjustment';
    case Migration = 'migration';
    case Chargeback = 'chargeback';
    case Expiry = 'expiry';

    /** Returns true for debit-type sources (informational only — type field is authoritative). */
    public function isDebit(): bool
    {
        return match ($this) {
            self::CheckoutApplied, self::Expiry => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual Adjustment',
            self::Coupon => 'Coupon',
            self::Refund => 'Refund',
            self::CheckoutApplied => 'Applied at Checkout',
            self::SubscriptionCycle => 'Subscription Cycle',
            self::Adjustment => 'Adjustment',
            self::Migration => 'Migration',
            self::Chargeback => 'Chargeback',
            self::Expiry => 'Credit Expired',
        };
    }
}
