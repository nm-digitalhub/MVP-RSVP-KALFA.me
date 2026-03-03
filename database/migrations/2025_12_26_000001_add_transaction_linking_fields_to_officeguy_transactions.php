<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add missing refund_transaction_id field and indexes.
     * Note: parent_transaction_id, transaction_type, and payment_token already exist.
     */
    public function up(): void
    {
        if (! Schema::hasTable('officeguy_transactions')) {
            return;
        }

        Schema::table('officeguy_transactions', function (Blueprint $table) {
            // Add refund_transaction_id (reverse link: charge -> refund)
            // Note: parent_transaction_id doesn't exist in initial migration, adding at the end
            if (!Schema::hasColumn('officeguy_transactions', 'refund_transaction_id')) {
                $table->foreignId('refund_transaction_id')
                    ->nullable()
                    ->constrained('officeguy_transactions')
                    ->onDelete('set null')
                    ->comment('Refund transaction ID (populated when charge is refunded)');
            }
        });

        // Add indexes only if columns exist (they don't exist in initial migration)
        if (Schema::hasColumn('officeguy_transactions', 'transaction_type')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_transaction_type ON officeguy_transactions(transaction_type)');
        }
        if (Schema::hasColumn('officeguy_transactions', 'payment_token')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_payment_token ON officeguy_transactions(payment_token)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS idx_transaction_type ON officeguy_transactions');
        DB::statement('DROP INDEX IF EXISTS idx_payment_token ON officeguy_transactions');

        // Drop refund_transaction_id field (keep other fields as they may be used elsewhere)
        Schema::table('officeguy_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('officeguy_transactions', 'refund_transaction_id')) {
                $table->dropForeign(['refund_transaction_id']);
                $table->dropColumn('refund_transaction_id');
            }
        });
    }
};
