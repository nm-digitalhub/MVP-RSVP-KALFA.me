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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('description')->nullable();
            $table->string('discount_type', 32); // percentage | fixed | trial_extension
            $table->unsignedInteger('discount_value'); // percent (0-100) | agorot | days
            $table->string('target_type', 32)->default('global'); // global | subscription | plan | event_billing
            $table->json('target_ids')->nullable(); // ProductPlan IDs when target_type = plan
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_account')->nullable();
            $table->boolean('first_time_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
