<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A period a room was under maintenance (RLNK-34). Opened and closed by the
 * room's status, see Room::booted().
 */
class RoomMaintenance extends Model
{
    protected $fillable = [
        'room_id',
        'room_issue_id',
        'started_by',
        'ended_by',
        'reason',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomIssue(): BelongsTo
    {
        return $this->belongsTo(RoomIssue::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function ender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
}
