<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Rooms created before the qr_code column existed have no code, so their
     * QR page and the faculty scan / manual room selection cannot address
     * them. Give each such room a code in the same format the Room model uses.
     */
    public function up(): void
    {
        DB::table('rooms')
            ->whereNull('qr_code')
            ->orderBy('id')
            ->each(function (object $room): void {
                do {
                    $code = 'ROOM-'.strtoupper(Str::random(10));
                } while (DB::table('rooms')->where('qr_code', $code)->exists());

                DB::table('rooms')->where('id', $room->id)->update(['qr_code' => $code]);
            });
    }

    /**
     * Codes may already be printed and posted on doors, so they are kept.
     */
    public function down(): void
    {
        //
    }
};
