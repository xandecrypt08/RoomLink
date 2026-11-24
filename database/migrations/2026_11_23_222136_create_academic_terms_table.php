<?php

use App\Models\AcademicTerm;
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
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('school_year'); // the year it starts, 2026 for S.Y. 2026-2027
            $table->unsignedTinyInteger('semester');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['school_year', 'semester']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('academic_term_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        // Schedules entered before terms existed belong to the running term.
        if (DB::table('class_sessions')->exists()) {
            DB::table('class_sessions')->update(['academic_term_id' => AcademicTerm::current()->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_term_id');
        });

        Schema::dropIfExists('academic_terms');
    }
};
