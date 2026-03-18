<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_credit_transactions', function (Blueprint $table) {
            $table->dropIndex('account_credit_transactions_reference_type_reference_id_index');
            $table->dropIndex('acct_credit_tx_account_currency_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_credit_transactions', function (Blueprint $table) {
            $table->index(
                ['reference_type', 'reference_id'],
                'account_credit_transactions_reference_type_reference_id_index'
            );

            $table->index(
                ['account_id', 'currency'],
                'acct_credit_tx_account_currency_idx'
            );
        });
    }
};
