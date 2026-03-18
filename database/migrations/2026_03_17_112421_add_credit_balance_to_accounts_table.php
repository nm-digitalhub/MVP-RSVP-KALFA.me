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
        Schema::table('accounts', function (Blueprint $table) {
            $table->integer('credit_balance_agorot')->notNull()->default(0)->after('sumit_customer_id');
        });

        // PostgreSQL only — SQLite (used in tests) does not support ADD CONSTRAINT via ALTER TABLE
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE accounts ADD CONSTRAINT accounts_credit_balance_non_negative CHECK (credit_balance_agorot >= 0)');
        }
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('credit_balance_agorot');
        });
    }
};
