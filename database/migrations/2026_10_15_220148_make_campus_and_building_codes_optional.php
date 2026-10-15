<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The campus and building forms treat these fields as optional, but the
     * columns were NOT NULL, so leaving one blank crashed the save. Campus codes
     * keep their unique index; it allows any number of blank codes.
     */
    public function up(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->string('campus_code')->nullable()->change();
            $table->text('address')->nullable()->change();
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->string('building_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->string('campus_code')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->string('building_code')->nullable(false)->change();
        });
    }
};
