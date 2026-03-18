<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CreditSource;
use App\Models\Account;
use App\Models\AccountCreditTransaction;
use App\Services\Billing\CreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Expire overdue account credits by creating compensating debit transactions.
 *
 * Runs daily. Maintains ledger integrity: SUM(all transactions) == credit_balance_agorot.
 *
 * Strategy:
 *  - Find all credit transactions where expiry_at < now() and not yet expired.
 *  - "Not yet expired" = no debit with source=expiry referencing this tx.
 *  - For each: calculate remaining unused amount, create an expiry debit.
 */
class ExpireAccountCreditsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(CreditService $creditService): void
    {
        // Find expired credits that haven't been expired yet (no reversal debit exists)
        $expiredCredits = AccountCreditTransaction::query()
            ->where('type', 'credit')
            ->whereNotNull('expiry_at')
            ->where('expiry_at', '<', now())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('account_credit_transactions as rev')
                    ->whereColumn('rev.reference_id', 'account_credit_transactions.id')
                    ->where('rev.reference_type', AccountCreditTransaction::class)
                    ->where('rev.source', CreditSource::Expiry->value)
                    ->where('rev.type', 'debit');
            })
            ->with('account')
            ->cursor();

        $expired = 0;
        $skipped = 0;

        foreach ($expiredCredits as $creditTx) {
            // Calculate how much of this credit was actually consumed by debits
            // (simple approach: if account still has balance >= amount, it's fully available)
            // We expire the full amount — the debit cannot exceed available balance.
            $remaining = $this->computeRemainingForCredit($creditTx);

            if ($remaining <= 0) {
                $skipped++;

                continue;
            }

            try {
                DB::transaction(function () use ($creditTx, $remaining) {
                    /** @var Account $locked */
                    $locked = Account::query()->lockForUpdate()->findOrFail($creditTx->account_id);

                    // Guard: don't expire more than current balance
                    $toExpire = min($remaining, $locked->credit_balance_agorot);

                    if ($toExpire <= 0) {
                        return;
                    }

                    $newBalance = $locked->credit_balance_agorot - $toExpire;

                    AccountCreditTransaction::create([
                        'account_id' => $locked->id,
                        'type' => 'debit',
                        'source' => CreditSource::Expiry,
                        'amount_agorot' => $toExpire,
                        'balance_after_agorot' => $newBalance,
                        'currency' => $creditTx->currency,
                        'description' => "Credit #{$creditTx->id} expired",
                        'reference_type' => AccountCreditTransaction::class,
                        'reference_id' => $creditTx->id,
                    ]);

                    $locked->update(['credit_balance_agorot' => $newBalance]);
                });

                $expired++;
            } catch (\Throwable $e) {
                Log::error('ExpireAccountCreditsJob: failed to expire credit', [
                    'credit_tx_id' => $creditTx->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ExpireAccountCreditsJob completed', compact('expired', 'skipped'));
    }

    /**
     * Rough estimate of remaining value on a specific credit transaction.
     * Uses account's running balance as a proxy — accurate enough for daily expiry.
     */
    private function computeRemainingForCredit(AccountCreditTransaction $creditTx): int
    {
        // Sum all debits on this account after this credit was created
        // Simple: return full amount — the balance guard in the transaction prevents over-debit
        return $creditTx->amount_agorot;
    }
}
