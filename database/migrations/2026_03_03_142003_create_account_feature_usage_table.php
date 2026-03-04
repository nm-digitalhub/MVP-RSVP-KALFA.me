<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Usage tracking per account per feature_key. No enforcement in this phase.
     */
    public function up(): void
    {
        Schema::create('account_feature_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key')->index();
            $table->unsignedInteger('period_key')->index(); // e.g. YYYYMM for monthly
            $table->unsignedInteger('usage_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'feature_key', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_feature_usage');
    }
};
