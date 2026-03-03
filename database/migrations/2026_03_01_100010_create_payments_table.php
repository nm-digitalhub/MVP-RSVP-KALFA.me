<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 3)->default('ILS');
            $table->string('status', 20); // pending, succeeded, failed, refunded, cancelled
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->unique('gateway_transaction_id');
            $table->index(['organization_id', 'status']);
            $table->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
