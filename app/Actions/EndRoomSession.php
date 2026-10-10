<?php

namespace App\Actions;

use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EndRoomSession
{
    /**
     * Close an active session and release the room.
     *
     * @param  string  $status  How the session closed: ended, transferred or not_held.
     */
    public function handle(
        RoomSession $session,
        string $status = RoomSession::STATUS_ENDED,
        ?string $reason = null,
    ): RoomSession {
        if (! in_array($status, [RoomSession::STATUS_ENDED, RoomSession::STATUS_TRANSFERRED, RoomSession::STATUS_NOT_HELD], true)) {
            throw new InvalidArgumentException("Invalid closing status [{$status}].");
        }

        return DB::transaction(function () use ($session, $status, $reason): RoomSession {
            $room = Room::query()->lockForUpdate()->findOrFail($session->room_id);
            $session->refresh();

            if (! $session->isActive()) {
                return $session;
            }

            $session->update([
                'status' => $status,
                'ended_at' => now(),
                'reason' => $reason,
            ]);

            // Maintenance set while the class was running stays in place.
            if ($room->status === 'occupied') {
                $room->update(['status' => 'available']);
            }

            return $session;
        });
    }
}
