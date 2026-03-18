<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CreditSource;
use App\Exceptions\AlreadyReversedException;
use App\Exceptions\InsufficientCreditException;
use App\Jobs\ExpireAccountCreditsJob;
use App\Models\Account;
use App\Services\Billing\CreditApplicationResult;
use App\Services\Billing\CreditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private CreditService $sut;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = app(CreditService::class);
        $this->account = Account::factory()->create(['credit_balance_agorot' => 0]);
    }

    // ─── credit() ─────────────────────────────────────────────────────────────

    public function test_credit_adds_to_balance(): void
    {
        $tx = $this->sut->credit($this->account, 1000, CreditSource::Manual, 'Test credit');

        $this->assertEquals(1000, $this->account->fresh()->credit_balance_agorot);
        $this->assertEquals('credit', $tx->type);
        $this->assertEquals(1000, $tx->amount_agorot);
        $this->assertEquals(1000, $tx->balance_after_agorot);
    }

    public function test_credit_rejects_zero_or_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sut->credit($this->account, 0, CreditSource::Manual, 'bad');
    }

    public function test_multiple_credits_accumulate(): void
    {
        $this->sut->credit($this->account, 500, CreditSource::Manual, 'first');
        $this->sut->credit($this->account, 300, CreditSource::Coupon, 'second');

        $this->assertEquals(800, $this->account->fresh()->credit_balance_agorot);
    }

    // ─── debit() ──────────────────────────────────────────────────────────────

    public function test_debit_reduces_balance(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Manual, 'seed');
        $tx = $this->sut->debit($this->account, 400, CreditSource::CheckoutApplied, 'checkout');

        $this->assertEquals(600, $this->account->fresh()->credit_balance_agorot);
        $this->assertEquals('debit', $tx->type);
        $this->assertEquals(400, $tx->amount_agorot);
        $this->assertEquals(600, $tx->balance_after_agorot);
    }

    public function test_debit_throws_when_insufficient(): void
    {
        $this->sut->credit($this->account, 200, CreditSource::Manual, 'seed');

        $this->expectException(InsufficientCreditException::class);
        $this->sut->debit($this->account, 500, CreditSource::CheckoutApplied, 'too much');
    }

    public function test_debit_rejects_zero_or_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sut->debit($this->account, 0, CreditSource::CheckoutApplied, 'bad');
    }

    // ─── getAvailableBalance() ────────────────────────────────────────────────

    public function test_available_balance_excludes_expired_credits(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Manual, 'valid');
        $this->sut->credit($this->account, 500, CreditSource::Coupon, 'expired soon',
            expiresAt: Carbon::yesterday());

        // Balance column includes both, but available should exclude expired
        $available = $this->sut->getAvailableBalance($this->account);
        $this->assertEquals(1000, $available);
    }

    public function test_available_balance_includes_non_expired(): void
    {
        $this->sut->credit($this->account, 700, CreditSource::Manual, 'valid',
            expiresAt: Carbon::tomorrow());

        $this->assertEquals(700, $this->sut->getAvailableBalance($this->account));
    }

    public function test_available_balance_is_zero_when_all_expired(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Manual, 'expired',
            expiresAt: Carbon::yesterday());

        $this->assertEquals(0, $this->sut->getAvailableBalance($this->account));
    }

    // ─── applyToCheckout() ────────────────────────────────────────────────────

    public function test_apply_to_checkout_deducts_full_when_balance_covers(): void
    {
        $this->sut->credit($this->account, 2000, CreditSource::Manual, 'seed');

        $result = $this->sut->applyToCheckout($this->account, 1500);

        $this->assertInstanceOf(CreditApplicationResult::class, $result);
        $this->assertEquals(1500, $result->applied);
        $this->assertEquals(0, $result->remainingCharge);
        $this->assertTrue($result->hasCreditsApplied());
    }

    public function test_apply_to_checkout_deducts_partial_when_balance_insufficient(): void
    {
        $this->sut->credit($this->account, 300, CreditSource::Manual, 'seed');

        $result = $this->sut->applyToCheckout($this->account, 1000);

        $this->assertEquals(300, $result->applied);
        $this->assertEquals(700, $result->remainingCharge);
    }

    public function test_apply_to_checkout_returns_zero_when_no_balance(): void
    {
        $result = $this->sut->applyToCheckout($this->account, 1000);

        $this->assertEquals(0, $result->applied);
        $this->assertEquals(1000, $result->remainingCharge);
        $this->assertFalse($result->hasCreditsApplied());
        $this->assertNull($result->transaction);
    }

    public function test_apply_to_checkout_ignores_expired_credits(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Coupon, 'expired',
            expiresAt: Carbon::yesterday());

        $result = $this->sut->applyToCheckout($this->account, 500);

        $this->assertEquals(0, $result->applied);
        $this->assertEquals(500, $result->remainingCharge);
    }

    // ─── reverseDebit() ───────────────────────────────────────────────────────

    public function test_reverse_debit_restores_balance(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Manual, 'seed');
        $debitTx = $this->sut->debit($this->account, 400, CreditSource::CheckoutApplied, 'checkout');

        $this->assertEquals(600, $this->account->fresh()->credit_balance_agorot);

        $this->sut->reverseDebit($debitTx);

        $this->assertEquals(1000, $this->account->fresh()->credit_balance_agorot);
    }

    public function test_reverse_debit_is_idempotent(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Manual, 'seed');
        $debitTx = $this->sut->debit($this->account, 400, CreditSource::CheckoutApplied, 'checkout');

        $this->sut->reverseDebit($debitTx);

        $this->expectException(AlreadyReversedException::class);
        $this->sut->reverseDebit($debitTx);
    }

    public function test_reverse_debit_rejects_non_debit_tx(): void
    {
        $creditTx = $this->sut->credit($this->account, 1000, CreditSource::Manual, 'seed');

        $this->expectException(\InvalidArgumentException::class);
        $this->sut->reverseDebit($creditTx);
    }

    // ─── ExpireAccountCreditsJob ──────────────────────────────────────────────

    public function test_expire_job_creates_debit_for_expired_credit(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Coupon, 'expires',
            expiresAt: Carbon::yesterday());

        // Manually set balance to reflect the credit (normally done by credit())
        $this->account->refresh();

        (new ExpireAccountCreditsJob)->handle($this->sut);

        // Available balance should now be 0
        $this->assertEquals(0, $this->sut->getAvailableBalance($this->account));

        // An expiry debit transaction should exist
        $this->assertDatabaseHas('account_credit_transactions', [
            'account_id' => $this->account->id,
            'type' => 'debit',
            'source' => CreditSource::Expiry->value,
        ]);
    }

    public function test_expire_job_does_not_double_expire(): void
    {
        $this->sut->credit($this->account, 1000, CreditSource::Coupon, 'expires',
            expiresAt: Carbon::yesterday());

        $job = new ExpireAccountCreditsJob;
        $job->handle($this->sut);
        $job->handle($this->sut); // second run

        $expiryDebits = \App\Models\AccountCreditTransaction::query()
            ->where('account_id', $this->account->id)
            ->where('source', CreditSource::Expiry->value)
            ->count();

        $this->assertEquals(1, $expiryDebits);
    }

    // ─── getHistory() ─────────────────────────────────────────────────────────

    public function test_get_history_returns_paginated_transactions(): void
    {
        $this->sut->credit($this->account, 500, CreditSource::Manual, 'tx1');
        $this->sut->credit($this->account, 300, CreditSource::Coupon, 'tx2');

        $history = $this->sut->getHistory($this->account, perPage: 10);

        $this->assertEquals(2, $history->total());
    }
}
