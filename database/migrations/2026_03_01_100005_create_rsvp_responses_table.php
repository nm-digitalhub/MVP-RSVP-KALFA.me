<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsvp_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('response', 10); // yes, no, maybe
            $table->unsignedSmallInteger('attendees_count')->nullable();
            $table->text('message')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('invitation_id');
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvp_responses');
    }
};
