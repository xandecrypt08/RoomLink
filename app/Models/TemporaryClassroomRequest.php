<?php

namespace App\Models;

use App\Notifications\TemporaryClassroomRequestReviewed;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryClassroomRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'requester_class_session_id',
        'room_id',
        'scheduled_class_session_id',
        'scheduled_faculty_id',
        'reason',
        'status',
        'decision_note',
        'decided_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Notify the requester when a pending request is approved or rejected,
     * however the decision is made.
     */
    protected static function booted(): void
    {
        static::updated(function (TemporaryClassroomRequest $request): void {
            $wasDecided = $request->wasChanged('status')
                && $request->getOriginal('status') === 'pending'
                && in_array($request->status, ['approved', 'rejected'], true);

            if ($wasDecided) {
                $request->requester?->user?->notify(new TemporaryClassroomRequestReviewed($request));
            }
        });
    }

    public function requester()
    {
        return $this->belongsTo(Faculty::class, 'requester_id');
    }

    public function requesterClassSession()
    {
        return $this->belongsTo(ClassSession::class, 'requester_class_session_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function scheduledClassSession()
    {
        return $this->belongsTo(ClassSession::class, 'scheduled_class_session_id');
    }

    public function scheduledFaculty()
    {
        return $this->belongsTo(Faculty::class, 'scheduled_faculty_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * A request only covers the day it was made and, when a class was
     * scheduled in the room, that class's time slot. After that it can no
     * longer be decided on or used to start a session.
     */
    public function isExpiredAt(CarbonInterface $moment): bool
    {
        if (! $this->created_at->isSameDay($moment)) {
            return true;
        }

        $scheduledClass = $this->scheduledClassSession;

        return $scheduledClass !== null
            && $scheduledClass->end_time->format('H:i:s') <= $moment->format('H:i:s');
    }

    /**
     * Only the faculty scheduled in the room decides, and only while the
     * request is still pending and has not expired.
     */
    public function canBeDecidedBy(Faculty $faculty, CarbonInterface $moment): bool
    {
        return $this->isPending()
            && $this->scheduled_faculty_id === $faculty->id
            && ! $this->isExpiredAt($moment);
    }

    /**
     * The status to show: a pending request whose time has passed is shown as expired.
     */
    public function displayStatusAt(CarbonInterface $moment): string
    {
        return $this->isPending() && $this->isExpiredAt($moment)
            ? 'expired'
            : $this->status;
    }
}
