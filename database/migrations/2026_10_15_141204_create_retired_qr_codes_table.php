<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * QR codes a room used before its code was regenerated. Scanning one is
     * refused with a "replaced" message, and the code is never issued again.
     */
    public function up(): void
    {
        Schema::create('retired_qr_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            $table->string('qr_code', 100)->unique();

            $table->dateTime('retired_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retired_qr_codes');
    }
};
