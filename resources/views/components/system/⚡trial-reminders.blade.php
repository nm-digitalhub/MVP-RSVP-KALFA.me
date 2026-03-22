<?php

declare(strict_types=1);

namespace App\Livewire\System;

use App\Enums\AccountSubscriptionStatus;
use App\Jobs\SendTrialExpiringReminderJob;
use App\Models\AccountSubscription;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.system')]
#[Title('Trial Reminders')]
final class TrialReminders extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all'; // all, active, expiring_soon, expired

    public function render(): \Illuminate\View\View
    {
        $query = AccountSubscription::with(['account.owner', 'plan'])
            ->where('status', AccountSubscriptionStatus::Trial->value)
            ->orderBy('trial_ends_at', 'desc');

        // Apply search
        if ($this->search !== '') {
            $query->whereHas('account', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })->orWhereHas('account.owner', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter === 'expiring_soon') {
            $query->whereBetween('trial_ends_at', [now(), now()->addDays(7)]);
        } elseif ($this->statusFilter === 'expired') {
            $query->where('trial_ends_at', '<', now());
        } elseif ($this->statusFilter === 'active') {
            $query->where('trial_ends_at', '>=', now());
        }

        $trials = $query->paginate(25);

        return view('livewire.system.trial-reminders', [
            'trials' => $trials,
        ]);
    }

    public function sendReminder(int $subscriptionId): void
    {
        $trial = AccountSubscription::with(['account', 'account.owner', 'plan'])->find($subscriptionId);

        if ($trial === null) {
            $this->dispatch('notify', message: __('Trial not found.'), type: 'error');
            return;
        }

        if ($trial->status !== AccountSubscriptionStatus::Trial->value) {
            $this->dispatch('notify', message: __('This is not a trial subscription.'), type: 'error');
            return;
        }

        $daysRemaining = now()->diffInDays($trial->trial_ends_at, false);

        if ($daysRemaining < 0) {
            $this->dispatch('notify', message: __('This trial has already expired.'), type: 'error');
            return;
        }

        try {
            dispatch(new SendTrialExpiringReminderJob($trial, max(1, $daysRemaining)));

            Log::info('Manual trial reminder sent', [
                'subscription_id' => $trial->id,
                'account_id' => $trial->account_id,
                'days_remaining' => $daysRemaining,
            ]);

            $this->dispatch('notify', message: __('Reminder sent successfully.'), type: 'success');
        } catch (\Throwable $e) {
            Log::error('Failed to send manual trial reminder', [
                'subscription_id' => $trial->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', message: __('Failed to send reminder.'), type: 'error');
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function getDaysRemainingProperty(): array
    {
        $trials = AccountSubscription::where('status', AccountSubscriptionStatus::Trial->value)
            ->where('trial_ends_at', '>=', now())
            ->get();

        $expiringSoon = 0;
        $expiringVerySoon = 0;

        foreach ($trials as $trial) {
            $days = now()->diffInDays($trial->trial_ends_at, false);
            if ($days <= 3) {
                $expiringVerySoon++;
            }
            if ($days <= 7) {
                $expiringSoon++;
            }
        }

        return [
            'total' => $trials->count(),
            'expiring_soon' => $expiringSoon,
            'expiring_very_soon' => $expiringVerySoon,
        ];
    }
}
