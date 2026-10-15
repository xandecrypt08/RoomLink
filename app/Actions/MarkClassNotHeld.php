<?php

namespace App\Actions;

use App\Exceptions\ClassNotHeldException;
use App\Models\ClassSession;
use App\Models\RoomSession;
use Illuminate\Support\Facades\DB;

class MarkClassNotHeld
{
    public function __construct(private EndRoomSession $endRoomSession) {}

    /**
     * Record that today's occurrence of a scheduled class did not take place.
     *
     * If the class was already started, its session is closed as not held and
     * the room is released. Otherwise a not-held entry is written to the
     * session log so reports show the missed class.
     *
     * @throws ClassNotHeldException when the class is not today's or was already held or marked.
     */
    public function handle(ClassSession $schedule, string $reason): RoomSession
    {
        $today = now();

        if ($schedule->day !== ClassSession::dayCodeFor($today) || $schedule->status !== 'active') {
            throw ClassNotHeldException::notScheduledToday($schedule);
        }

        return DB::transaction(function () use ($schedule, $reason, $today): RoomSession {
            $todaysSessions = $schedule->roomSessions()
                ->whereDate('started_at', $today->toDateString())
                ->lockForUpdate()
                ->get();

            if ($todaysSessions->contains('status', RoomSession::STATUS_NOT_HELD)) {
                throw ClassNotHeldException::alreadyMarked($schedule);
            }

            $activeSession = $todaysSessions->firstWhere('status', RoomSession::STATUS_ACTIVE);

            if ($activeSession !== null) {
                return $this->endRoomSession->handle($activeSession, RoomSession::STATUS_NOT_HELD, $reason);
            }

            if ($todaysSessions->isNotEmpty()) {
                throw ClassNotHeldException::alreadyHeld($schedule);
            }

            return $schedule->roomSessions()->create([
                'room_id' => $schedule->room_id,
                'faculty_id' => $schedule->faculty_id,
                'status' => RoomSession::STATUS_NOT_HELD,
                'started_at' => $today->copy()->setTimeFromTimeString($schedule->start_time->format('H:i:s')),
                'ended_at' => $today,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Whether today's occurrence of the class has been marked not held.
     */
    public static function isMarkedToday(ClassSession $schedule): bool
    {
        return $schedule->roomSessions()
            ->where('status', RoomSession::STATUS_NOT_HELD)
            ->whereDate('started_at', now()->toDateString())
            ->exists();
    }
}
