<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Entitlements granted by a product. feature_key = free-form string (no predefined keys).
     */
    public function up(): void
    {
        Schema::create('product_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key')->index(); // free-form, e.g. "events_per_month", "max_guests"
            $table->string('value')->nullable(); // limit value or flag, e.g. "10", "unlimited"
            $table->json('constraints')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_entitlements');
    }
};
