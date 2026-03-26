<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('call_sid', 40)->nullable()->after('responded_at')->index();
            $table->string('call_status', 20)->nullable()->after('call_sid');
            $table->unsignedInteger('call_duration')->nullable()->after('call_status');
            $table->timestamp('call_initiated_at')->nullable()->after('call_duration');
            $table->timestamp('call_ended_at')->nullable()->after('call_initiated_at');
            $table->json('call_metadata')->nullable()->after('call_ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropIndex(['call_sid']);
            $table->dropColumn([
                'call_sid',
                'call_status',
                'call_duration',
                'call_initiated_at',
                'call_ended_at',
                'call_metadata',
            ]);
        });
    }
};
