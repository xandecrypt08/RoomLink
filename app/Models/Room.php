<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function temporaryClassroomRequests()
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
}
