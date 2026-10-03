<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->unsignedTinyInteger('floor_number')
                ->after('building_id');

            $table->text('description')
                ->nullable()
                ->after('floor_number');
        });
    }

    public function down(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->dropColumn(['floor_number', 'description']);
        });
    }
};