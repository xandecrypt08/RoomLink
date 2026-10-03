<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('building_name')
                ->after('campus_id');

            $table->string('building_code')
                ->after('building_name');

            $table->unsignedTinyInteger('number_of_floors')
                ->default(1)
                ->after('building_code');

            $table->text('description')
                ->nullable()
                ->after('number_of_floors');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn([
                'building_name',
                'building_code',
                'number_of_floors',
                'description',
            ]);
        });
    }
};