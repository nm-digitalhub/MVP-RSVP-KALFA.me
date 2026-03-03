<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * User may view payment status only if they belong to the payment's organization.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->organizations()->where('organizations.id', $payment->organization_id)->exists();
    }
}
