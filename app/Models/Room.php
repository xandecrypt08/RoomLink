<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'room_name',
        'qr_code',
        'capacity',
        'status',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            if (empty($room->qr_code)) {
                do {
                    $code = 'ROOM-'.strtoupper(Str::random(10));
                } while (self::where('qr_code', $code)->exists());

                $room->qr_code = $code;
            }
        });
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class);
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * Every actual use of this room (the session log).
     */
    public function roomSessions(): HasMany
    {
        return $this->hasMany(RoomSession::class);
    }

    /**
     * The session currently running in this room, if any.
     */
    public function activeSession(): HasOne
    {
        return $this->hasOne(RoomSession::class)->active();
    }

    public function temporaryClassroomRequests(): HasMany
    {
        return $this->hasMany(TemporaryClassroomRequest::class);
    }

    /**
     * Find the active class session scheduled in this room at the given moment.
     */
    public function scheduledClassSessionAt(CarbonInterface $moment): ?ClassSession
    {
        return $this->classSessions()
            ->with(['faculty', 'subject', 'section'])
            ->where('day', ClassSession::dayCodeFor($moment))
            ->where('status', 'active')
            ->get()
            ->first(fn (ClassSession $session) => $session->isOngoingAt($moment));
    }

    public function isUnderMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }
}
