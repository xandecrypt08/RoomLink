<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each row is one actual use of a room: started by a faculty member,
     * optionally for a scheduled class, and later ended, transferred or
     * marked not held. class_sessions remains the weekly timetable.
     */
    public function up(): void
    {
        Schema::create('room_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->restrictOnDelete();

            $table->foreignId('faculty_id')
                ->constrained('faculties')
                ->restrictOnDelete();

            // Null for temporary sessions that are not part of the timetable.
            $table->foreignId('class_session_id')
                ->nullable()
                ->constrained('class_sessions')
                ->nullOnDelete();

            $table->enum('status', [
                'active',
                'ended',
                'transferred',
                'not_held',
            ])->default('active');

            // Equals room_id while the session is active and null otherwise,
            // so the unique index allows only one active session per room.
            $table->foreignId('active_room_id')
                ->nullable()
                ->unique();

            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index(['room_id', 'started_at']);
            $table->index(['faculty_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_sessions');
    }
};
