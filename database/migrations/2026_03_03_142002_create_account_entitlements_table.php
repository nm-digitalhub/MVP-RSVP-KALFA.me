<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Grants: which account has which feature_key (from product or manual). No enforcement in this phase.
     */
    public function up(): void
    {
        Schema::create('account_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key')->index();
            $table->string('value')->nullable();
            $table->foreignId('product_entitlement_id')->nullable()->constrained('product_entitlements')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'feature_key']);
            $table->index(['account_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_entitlements');
    }
};
