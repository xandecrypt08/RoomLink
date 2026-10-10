<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomSession extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_NOT_HELD = 'not_held';

    protected $fillable = [
        'room_id',
        'faculty_id',
        'class_session_id',
        'status',
        'started_at',
        'ended_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Keep active_room_id in sync with the status so the database's unique
     * index enforces a single active session per room.
     */
    protected static function booted(): void
    {
        static::saving(function (RoomSession $session): void {
            $session->active_room_id = $session->isActive() ? $session->room_id : null;
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * @param  Builder<RoomSession>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
