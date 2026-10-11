<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link each faculty record to its login account, then backfill the link
     * for existing records by matching faculties.employee_id to users.employee_student_id.
     */
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $facultyUsers = DB::table('users')
            ->where('role', 'faculty')
            ->pluck('id', 'employee_student_id');

        DB::table('faculties')
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (object $faculty) use ($facultyUsers): void {
                if (isset($facultyUsers[$faculty->employee_id])) {
                    DB::table('faculties')
                        ->where('id', $faculty->id)
                        ->update(['user_id' => $facultyUsers[$faculty->employee_id]]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
