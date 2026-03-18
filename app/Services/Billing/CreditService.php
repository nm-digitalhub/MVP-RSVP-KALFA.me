<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\CreditSource;
use App\Exceptions\AlreadyReversedException;
use App\Exceptions\InsufficientCreditException;
use App\Models\Account;
use App\Models\AccountCreditTransaction;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Manages the account credit ledger.
 *
 * All mutations are atomic (DB transaction + SELECT FOR UPDATE on accounts row).
 * The ledger is append-only — no rows are ever updated or deleted.
 *
 * Balance unit: agorot (integer). 100 agorot = 1 ILS.
 */
class CreditService
{
    private const BALANCE_CACHE_TTL = 60; // seconds

    // ─── Credit (add funds) ───────────────────────────────────────────────────

    /**
     * Add credits to an account.
     *
     * @throws \InvalidArgumentException if amountAgorot <= 0
     */
    public function credit(
        Account $account,
        int $amountAgorot,
        CreditSource $source,
        string $description,
        string $currency = 'ILS',
        ?Carbon $expiresAt = null,
        ?Model $reference = null,
        ?int $actorId = null,
    ): AccountCreditTransaction {
        if ($amountAgorot <= 0) {
            throw new \InvalidArgumentException("Credit amount must be positive, got {$amountAgorot}.");
        }

        return DB::transaction(function () use ($account, $amountAgorot, $source, $description, $currency, $expiresAt, $reference, $actorId) {
            /** @var Account $locked */
            $locked = Account::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = $locked->credit_balance_agorot + $amountAgorot;

            $tx = AccountCreditTransaction::create([
                'account_id' => $locked->id,
                'type' => 'credit',
                'source' => $source->value,
                'amount_agorot' => $amountAgorot,
                'balance_after_agorot' => $newBalance,
                'currency' => $currency,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'expiry_at' => $expiresAt,
                'actor_id' => $actorId,
            ]);

            $locked->credit_balance_agorot = $newBalance;
            $locked->save();

            $this->forgetBalanceCache($locked->id, $currency);

            return $tx;
        });
    }

    // ─── Debit (deduct funds) ─────────────────────────────────────────────────

    /**
     * Deduct credits from an account.
     *
     * @throws InsufficientCreditException if available balance < amountAgorot
     * @throws \InvalidArgumentException if amountAgorot <= 0
     */
    public function debit(
        Account $account,
        int $amountAgorot,
        CreditSource $source,
        string $description,
        string $currency = 'ILS',
        ?Model $reference = null,
        ?int $actorId = null,
    ): AccountCreditTransaction {
        if ($amountAgorot <= 0) {
            throw new \InvalidArgumentException("Debit amount must be positive, got {$amountAgorot}.");
        }

        return DB::transaction(function () use (
            $account,
            $amountAgorot,
            $source,
            $description,
            $currency,
            $reference,
            $actorId
        ) {
            /** @var Account $locked */
            $locked = Account::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $available = $this->computeAvailableBalance($locked->id, $currency);

            if ($available < $amountAgorot) {
                throw new InsufficientCreditException(
                    "Insufficient credits: available={$available}, requested={$amountAgorot}"
                );
            }

            $newBalance = $locked->credit_balance_agorot - $amountAgorot;

            $tx = AccountCreditTransaction::create([
                'account_id' => $locked->id,
                'type' => 'debit',
                'source' => $source->value,
                'amount_agorot' => $amountAgorot,
                'balance_after_agorot' => $newBalance,
                'currency' => $currency,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'actor_id' => $actorId,
            ]);

            $locked->credit_balance_agorot = $newBalance;
            $locked->save();

            $this->forgetBalanceCache($locked->id, $currency);

            return $tx;
        });
    }

    // ─── Apply at checkout ────────────────────────────────────────────────────

    /**
     * Automatically apply available credits at checkout.
     * Deducts min(availableBalance, chargeAgorot) using FIFO order.
     * Returns a CreditApplicationResult with how much was deducted.
     */
    public function applyToCheckout(
        Account $account,
        int $chargeAgorot,
        ?Model $reference = null,
        string $currency = 'ILS',
    ): CreditApplicationResult {
        $available = $this->getAvailableBalance($account, $currency);

        if ($available <= 0) {
            return new CreditApplicationResult(0, $chargeAgorot, null);
        }

        $toApply = min($available, $chargeAgorot);

        $tx = $this->debit(
            account: $account,
            amountAgorot: $toApply,
            source: CreditSource::CheckoutApplied,
            description: 'Applied at checkout',
            currency: $currency,
            reference: $reference,
        );

        return new CreditApplicationResult(
            applied: $toApply,
            remainingCharge: $chargeAgorot - $toApply,
            transaction: $tx,
        );
    }

    // ─── Reverse a debit ──────────────────────────────────────────────────────

    /**
     * Reverse a previous debit (e.g. failed checkout).
     * Creates a compensating credit linked to the original transaction.
     * Idempotent: throws AlreadyReversedException if already reversed.
     *
     * @throws AlreadyReversedException
     */
    public function reverseDebit(
        AccountCreditTransaction $debitTx,
        ?int $actorId = null,
    ): AccountCreditTransaction {
        if ($debitTx->type !== 'debit') {
            throw new \InvalidArgumentException('Only debit transactions can be reversed.');
        }

        // Idempotency check: look for an existing reversal referencing this tx
        $existing = AccountCreditTransaction::query()
            ->where('reference_type', AccountCreditTransaction::class)
            ->where('reference_id', $debitTx->id)
            ->where('type', 'credit')
            ->exists();

        if ($existing) {
            throw new AlreadyReversedException("Transaction {$debitTx->id} has already been reversed.");
        }

        return $this->credit(
            account: $debitTx->account,
            amountAgorot: $debitTx->amount_agorot,
            source: CreditSource::Adjustment,
            description: "Reversal of debit #{$debitTx->id}",
            currency: $debitTx->currency,
            reference: $debitTx,
            actorId: $actorId,
        );
    }

    // ─── Balance ──────────────────────────────────────────────────────────────

    /**
     * Available balance in agorot, expiry-aware.
     * Uses cached value (60s TTL) — invalidated on every mutation.
     */
    public function getAvailableBalance(Account $account, string $currency = 'ILS'): int
    {
        return Cache::remember(
            $this->balanceCacheKey($account->id, $currency),
            self::BALANCE_CACHE_TTL,
            fn () => $this->computeAvailableBalance($account->id, $currency),
        );
    }

    /**
     * Ledger history for an account (paginated, newest first).
     */
    public function getHistory(
        Account $account,
        int $perPage = 20,
        string $currency = 'ILS',
    ): LengthAwarePaginator {
        return AccountCreditTransaction::query()
            ->where('account_id', $account->id)
            ->where('currency', $currency)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    /**
     * Compute available balance directly from ledger (no cache).
     * Sums all credits (non-expired) minus all debits for the account.
     */
    private function computeAvailableBalance(int $accountId, string $currency): int
    {
        $credits = AccountCreditTransaction::query()
            ->where('account_id', $accountId)
            ->where('currency', $currency)
            ->where('type', 'credit')
            ->where(function ($q) {
                $q->whereNull('expiry_at')->orWhere('expiry_at', '>', now());
            })
            ->sum('amount_agorot');

        $debits = AccountCreditTransaction::query()
            ->where('account_id', $accountId)
            ->where('currency', $currency)
            ->where('type', 'debit')
            ->sum('amount_agorot');

        return max(0, (int) $credits - (int) $debits);
    }

    private function forgetBalanceCache(int $accountId, string $currency): void
    {
        Cache::forget($this->balanceCacheKey($accountId, $currency));
    }

    private function balanceCacheKey(int $accountId, string $currency): string
    {
        return "account:{$accountId}:credit_balance:{$currency}";
    }
}
