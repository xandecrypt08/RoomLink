<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('subject_code', 50)->unique()->after('id');
            $table->string('subject_name', 150)->after('subject_code');
            $table->text('description')->nullable()->after('subject_name');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'subject_code',
                'subject_name',
                'description',
            ]);
        });
    }
};