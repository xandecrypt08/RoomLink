<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a session started by a classroom transfer to the session it continues.
     */
    public function up(): void
    {
        Schema::table('room_sessions', function (Blueprint $table) {
            $table->foreignId('transferred_from_id')
                ->nullable()
                ->after('class_session_id')
                ->constrained('room_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('room_sessions', function (Blueprint $table) {
            $table->dropForeign(['transferred_from_id']);
            $table->dropColumn('transferred_from_id');
        });
    }
};
