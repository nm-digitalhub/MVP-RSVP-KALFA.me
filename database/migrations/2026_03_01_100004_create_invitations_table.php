<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token')->unique();
            $table->string('slug')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 20); // pending, sent, opened, responded, expired
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('event_id');
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
