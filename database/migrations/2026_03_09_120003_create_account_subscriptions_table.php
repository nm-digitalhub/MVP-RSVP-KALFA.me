<?php

declare(strict_types=1);

use App\Enums\AccountSubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_plan_id')->constrained('product_plans')->cascadeOnDelete();
            $table->string('status', 30)->default(AccountSubscriptionStatus::Trial->value)->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_subscriptions');
    }
};
