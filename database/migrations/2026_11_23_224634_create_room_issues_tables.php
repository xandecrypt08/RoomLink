<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->string('category', 30)->index();
            $table->text('description')->nullable();
            $table->boolean('qr_replacement_requested')->default(false);
            $table->string('status', 20)->default('pending')->index();
            $table->string('assigned_to', 150)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('room_issue_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('room_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_issue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        // Rooms already under maintenance get a record to close when restored.
        foreach (DB::table('rooms')->where('status', 'maintenance')->pluck('id') as $roomId) {
            DB::table('room_maintenances')->insert([
                'room_id' => $roomId,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_maintenances');
        Schema::dropIfExists('room_issue_updates');
        Schema::dropIfExists('room_issues');
    }
};
