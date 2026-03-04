<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Additive: nullable account_id on events_billing and payments. No business logic change.
     */
    public function up(): void
    {
        Schema::table('events_billing', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->index('account_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::table('events_billing', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropForeign(['account_id']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropForeign(['account_id']);
        });
    }
};
