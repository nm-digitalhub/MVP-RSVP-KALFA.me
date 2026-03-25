<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rsvp_responses', function (Blueprint $table) {
            $table->string('response_method', 20)->nullable()->after('user_agent');
            $table->string('call_sid', 40)->nullable()->after('response_method');
        });
    }

    public function down(): void
    {
        Schema::table('rsvp_responses', function (Blueprint $table) {
            $table->dropColumn(['response_method', 'call_sid']);
        });
    }
};
