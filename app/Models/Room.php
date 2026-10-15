<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
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
                $room->qr_code = self::newQrCode();
            }
        });
    }

    /**
     * A QR code that no room uses now or has used before.
     */
    public static function newQrCode(): string
    {
        do {
            $code = 'ROOM-'.strtoupper(Str::random(10));
        } while (
            self::where('qr_code', $code)->exists()
            || RetiredQrCode::where('qr_code', $code)->exists()
        );

        return $code;
    }

    /**
     * Give the room a new QR code and retire the old one so it can no longer be used.
     */
    public function regenerateQrCode(): string
    {
        $newCode = DB::transaction(function (): string {
            $room = self::query()->lockForUpdate()->findOrFail($this->id);

            $room->retiredQrCodes()->create([
                'qr_code' => $room->qr_code,
                'retired_at' => now(),
            ]);

            $room->update(['qr_code' => self::newQrCode()]);

            return $room->qr_code;
        });

        $this->refresh();

        return $newCode;
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

    /**
     * QR codes this room used before they were regenerated.
     */
    public function retiredQrCodes(): HasMany
    {
        return $this->hasMany(RetiredQrCode::class);
    }

    public function temporaryClassroomRequests(): HasMany
    {
        return $this->hasMany(TemporaryClassroomRequest::class);
    }

    /**
     * The address encoded in the room's QR code. It is built from APP_URL, not
     * from whatever address the admin happened to open the page with, because
     * printed QR codes must open on any phone on the school network.
     */
    public function scanUrl(): string
    {
        return rtrim((string) config('app.url'), '/')
            .route('faculty.sessions.scan', $this->qr_code, false);
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
