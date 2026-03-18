<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\AccountCreditTransaction;

/**
 * Result of applying credits at checkout.
 */
final class CreditApplicationResult
{
    public function __construct(
        /** Amount deducted from the credit balance (in agorot). */
        public readonly int $applied,
        /** Remaining charge after credit deduction (what SUMIT should charge). */
        public readonly int $remainingCharge,
        /** The debit transaction created, or null if no credits were available. */
        public readonly ?AccountCreditTransaction $transaction,
    ) {}

    public function hasCreditsApplied(): bool
    {
        return $this->applied > 0;
    }
}
