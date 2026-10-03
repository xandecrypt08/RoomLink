<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('floor_id')
                ->after('id')
                ->constrained('floors')
                ->cascadeOnDelete();

            $table->string('room_name')->after('floor_id');

            $table->unsignedInteger('capacity')
                ->default(0)
                ->after('room_name');

            $table->enum('status', [
                'available',
                'occupied',
                'maintenance'
            ])
                ->default('available')
                ->after('capacity');

            $table->text('description')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['floor_id']);
            $table->dropColumn([
                'floor_id',
                'room_name',
                'capacity',
                'status',
                'description'
            ]);
        });
    }
};