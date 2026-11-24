<?php

namespace App\Actions;

use App\Models\RoomSession;
use App\Notifications\SessionEndedAutomatically;
use Carbon\CarbonInterface;

/**
 * Ends sessions faculty forgot to end, so their rooms are released and the
 * utilization report isn't inflated.
 */
class EndForgottenSessions
{
    /**
     * How long after its class ends a session may still be running.
     */
    public const GRACE_MINUTES = 30;

    /**
     * How long a session without a scheduled class may run.
     */
    public const TEMPORARY_SESSION_MINUTES = 240;

    public const REASON = 'Ended automatically because it was not ended by the faculty member.';

    public function __construct(private EndRoomSession $endRoomSession) {}

    /**
     * @return int how many sessions were ended
     */
    public function handle(): int
    {
        $now = now();
        $ended = 0;

        foreach (RoomSession::active()->with(['classSession', 'faculty.user', 'room'])->get() as $session) {
            $expectedEnd = $this->expectedEnd($session);
            $grace = $session->classSession === null ? 0 : self::GRACE_MINUTES;

            if ($expectedEnd->copy()->addMinutes($grace)->isAfter($now)) {
                continue;
            }

            $session = $this->endRoomSession->handle($session, RoomSession::STATUS_ENDED, self::REASON, $expectedEnd);

            // The faculty member may have ended it at the same moment.
            if ($session->reason === self::REASON) {
                $session->faculty?->user?->notify(new SessionEndedAutomatically($session));
                $ended++;
            }
        }

        return $ended;
    }

    /**
     * When the session should have ended: with its class on the day it
     * started, or after the longest a temporary session may run.
     */
    private function expectedEnd(RoomSession $session): CarbonInterface
    {
        if ($session->classSession === null) {
            return $session->started_at->copy()->addMinutes(self::TEMPORARY_SESSION_MINUTES);
        }

        $classEnd = $session->started_at->copy()->setTimeFromTimeString($session->classSession->end_time->format('H:i:s'));

        return $classEnd->max($session->started_at);
    }
}
