<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->string('employee_id', 50)
                ->unique()
                ->after('id');

            $table->string('first_name', 100)
                ->after('employee_id');

            $table->string('middle_name', 100)
                ->nullable()
                ->after('first_name');

            $table->string('last_name', 100)
                ->after('middle_name');

            $table->string('email', 150)
                ->nullable()
                ->unique()
                ->after('last_name');

            $table->string('department', 150)
                ->nullable()
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->dropUnique(['email']);

            $table->dropColumn([
                'employee_id',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'department',
            ]);
        });
    }
};