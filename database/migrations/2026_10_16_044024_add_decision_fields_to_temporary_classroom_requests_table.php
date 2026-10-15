<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record when a temporary classroom request was approved or rejected
     * (RLNK-27/28) and the optional note the scheduled faculty left.
     */
    public function up(): void
    {
        Schema::table('temporary_classroom_requests', function (Blueprint $table) {
            $table->string('decision_note', 255)
                ->nullable()
                ->after('status');

            $table->timestamp('decided_at')
                ->nullable()
                ->after('decision_note');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_classroom_requests', function (Blueprint $table) {
            $table->dropColumn(['decision_note', 'decided_at']);
        });
    }
};
