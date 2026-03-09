<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('account_entitlements', 'type')) {
            Schema::table('account_entitlements', function (Blueprint $table): void {
                $table->string('type', 30)->nullable()->after('value')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('account_entitlements', 'type')) {
            Schema::table('account_entitlements', function (Blueprint $table): void {
                $table->dropColumn('type');
            });
        }
    }
};
