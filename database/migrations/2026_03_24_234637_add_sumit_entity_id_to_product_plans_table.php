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
        Schema::table('product_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('sumit_entity_id')->nullable()->after('sku');
            $table->index('sumit_entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_plans', function (Blueprint $table) {
            $table->dropIndex(['sumit_entity_id']);
            $table->dropColumn('sumit_entity_id');
        });
    }
};
