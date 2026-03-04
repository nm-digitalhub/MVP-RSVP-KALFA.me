<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Purchase abstraction: links account to a future payment/checkout. No enforcement in this phase.
     */
    public function up(): void
    {
        Schema::create('billing_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->index(); // draft, pending, completed, cancelled
            $table->string('intent_type', 50)->nullable()->index(); // e.g. event_checkout, subscription
            $table->nullableMorphs('payable'); // optional link to EventBilling, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_intents');
    }
};
