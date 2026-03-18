<?php

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
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('redeemed_by')->constrained('users')->cascadeOnDelete();
            $table->nullableMorphs('redeemable'); // AccountSubscription | EventBilling
            $table->unsignedInteger('discount_applied'); // in agorot (0 for trial extension)
            $table->unsignedInteger('trial_days_added')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['coupon_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
