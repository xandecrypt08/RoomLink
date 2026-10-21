<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each program belongs to a college, and optionally to a group within it
     * (the College of Education has Elementary and Secondary Education programs).
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('college_id')
                ->nullable()
                ->after('id')
                ->constrained('colleges')
                ->nullOnDelete();

            $table->string('program_group', 100)
                ->nullable()
                ->after('program_name');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropColumn(['college_id', 'program_group']);
        });
    }
};
