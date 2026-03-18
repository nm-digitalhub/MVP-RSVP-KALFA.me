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
        Schema::table('account_credit_transactions', function (Blueprint $table) {
            $table->index(['account_id', 'type'], 'acct_credit_tx_account_type_idx');
            $table->index(['account_id', 'currency'], 'acct_credit_tx_account_currency_idx');
            $table->index(['account_id', 'currency', 'type'], 'acct_credit_tx_account_currency_type_idx');
            $table->index(['type', 'expiry_at'], 'acct_credit_tx_type_expiry_idx');
            $table->index(['reference_type', 'reference_id'], 'acct_credit_tx_reference_idx');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS acct_credit_tx_account_type_idx');
        DB::statement('DROP INDEX IF EXISTS acct_credit_tx_account_currency_idx');
        DB::statement('DROP INDEX IF EXISTS acct_credit_tx_account_currency_type_idx');
        DB::statement('DROP INDEX IF EXISTS acct_credit_tx_type_expiry_idx');
        DB::statement('DROP INDEX IF EXISTS acct_credit_tx_reference_idx');
    }
};
