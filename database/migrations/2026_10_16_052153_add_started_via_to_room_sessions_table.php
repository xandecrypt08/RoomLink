<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record how each session was started: by scanning the room's QR code,
     * manually from Find a Room (with the faculty member's reason), or by a
     * transfer. Sessions logged before this, and not-held entries, stay null.
     */
    public function up(): void
    {
        Schema::table('room_sessions', function (Blueprint $table) {
            $table->string('started_via', 20)
                ->nullable()
                ->after('status');

            $table->string('manual_start_reason', 255)
                ->nullable()
                ->after('started_via');
        });
    }

    public function down(): void
    {
        Schema::table('room_sessions', function (Blueprint $table) {
            $table->dropColumn(['started_via', 'manual_start_reason']);
        });
    }
};
