<?php

namespace App\Models;

use Database\Factories\RetiredQrCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A QR code a room no longer uses because the administrator regenerated it.
 */
class RetiredQrCode extends Model
{
    /** @use HasFactory<RetiredQrCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'room_id',
        'qr_code',
        'retired_at',
    ];

    protected function casts(): array
    {
        return [
            'retired_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Message shown when someone scans or enters this retired code.
     */
    public function replacedMessage(): string
    {
        return "This QR code for {$this->room->room_name} was replaced on {$this->retired_at->format('M j, Y')} and no longer works. "
            .'Scan the new QR code posted in the room, or report the damaged QR code to the administrator.';
    }
}
