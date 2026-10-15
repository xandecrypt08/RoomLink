<?php

namespace App\Models;

use App\Notifications\TemporaryClassroomRequestReviewed;
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
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

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
}
