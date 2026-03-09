<?php

declare(strict_types=1);

use App\Enums\ProductPriceBillingCycle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_plan_id')->constrained('product_plans')->cascadeOnDelete();
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->string('billing_cycle', 30)->default(ProductPriceBillingCycle::Monthly->value)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
