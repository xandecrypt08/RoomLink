<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_room', function (Blueprint $table) {
            $table->foreignId('room_id')
                ->after('id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            $table->foreignId('facility_id')
                ->after('room_id')
                ->constrained('facilities')
                ->cascadeOnDelete();

            $table->unique(['room_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::table('facility_room', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropForeign(['facility_id']);
            $table->dropUnique(['room_id', 'facility_id']);
            $table->dropColumn(['room_id', 'facility_id']);
        });
    }
};