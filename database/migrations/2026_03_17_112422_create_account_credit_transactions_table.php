<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            $table->string('type', 10);    // 'credit' | 'debit'
            $table->string('source', 30);  // CreditSource enum values

            $table->integer('amount_agorot');         // always positive — enforced via CHECK
            $table->integer('balance_after_agorot');  // snapshot for audit trail
            $table->char('currency', 3)->default('ILS');

            $table->string('description', 255)->nullable();

            // Polymorphic reference (Coupon, Payment, AccountCreditTransaction for reversals)
            $table->nullableMorphs('reference');

            $table->timestamp('expiry_at')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // Append-only: created_at only, no updated_at
            $table->timestamp('created_at')->useCurrent();
        });

        // DB-level constraints — PostgreSQL only (SQLite used in tests does not support ADD CONSTRAINT)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE account_credit_transactions ADD CONSTRAINT acct_credit_tx_type_check CHECK (type IN ('credit', 'debit'))");
            DB::statement("ALTER TABLE account_credit_transactions ADD CONSTRAINT acct_credit_tx_source_check CHECK (source IN ('manual','coupon','refund','checkout_applied','subscription_cycle','adjustment','migration','chargeback','expiry'))");
            DB::statement('ALTER TABLE account_credit_transactions ADD CONSTRAINT acct_credit_tx_amount_positive CHECK (amount_agorot > 0)');
        }

        // Indexes
        Schema::table('account_credit_transactions', function (Blueprint $table) {
            $table->index(['account_id', 'created_at'], 'acct_credit_tx_account_created_idx');
            $table->index(['account_id', 'id'], 'acct_credit_tx_account_id_idx');
        });

        DB::statement('CREATE INDEX acct_credit_tx_expiry_idx ON account_credit_transactions (account_id, expiry_at) WHERE expiry_at IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('account_credit_transactions');
    }
};
