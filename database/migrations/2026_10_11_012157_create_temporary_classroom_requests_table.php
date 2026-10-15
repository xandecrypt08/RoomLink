<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_classroom_requests', function (Blueprint $table) {
            $table->id();

            // Faculty member asking to use the room
            $table->foreignId('requester_id')
                ->constrained('faculties')
                ->cascadeOnDelete();

            // Requester's own class that cannot use its assigned room
            $table->foreignId('requester_class_session_id')
                ->nullable()
                ->constrained('class_sessions')
                ->nullOnDelete();

            // Room scanned by the requester
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            // Class scheduled in the requested room when the request was made
            $table->foreignId('scheduled_class_session_id')
                ->nullable()
                ->constrained('class_sessions')
                ->nullOnDelete();

            $table->foreignId('scheduled_faculty_id')
                ->nullable()
                ->constrained('faculties')
                ->nullOnDelete();

            $table->text('reason');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
            ])->default('pending');

            $table->timestamps();

            $table->index(['scheduled_faculty_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_classroom_requests');
    }
};
