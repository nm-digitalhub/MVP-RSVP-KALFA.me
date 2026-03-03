<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_table_id')->constrained('event_tables')->cascadeOnDelete();
            $table->string('seat_number')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'guest_id']);
            $table->index(['event_id', 'event_table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_assignments');
    }
};
