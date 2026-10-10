<?php

namespace App\Actions;

use App\Exceptions\RoomUnavailableException;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class StartRoomSession
{
    /**
     * Start a session in the room and mark the room as occupied.
     *
     * @throws RoomUnavailableException when the room is under maintenance or already in use.
     */
    public function handle(Room $room, Faculty $faculty, ?ClassSession $classSession = null): RoomSession
    {
        try {
            return DB::transaction(function () use ($room, $faculty, $classSession): RoomSession {
                $room = Room::query()->lockForUpdate()->findOrFail($room->id);

                if ($room->isUnderMaintenance()) {
                    throw RoomUnavailableException::underMaintenance($room);
                }

                if ($room->activeSession()->exists()) {
                    throw RoomUnavailableException::occupied($room);
                }

                $session = $room->roomSessions()->create([
                    'faculty_id' => $faculty->id,
                    'class_session_id' => $classSession?->id,
                    'status' => RoomSession::STATUS_ACTIVE,
                    'started_at' => now(),
                ]);

                $room->update(['status' => 'occupied']);

                return $session;
            });
        } catch (UniqueConstraintViolationException) {
            // Another request started a session in this room at the same moment.
            throw RoomUnavailableException::occupied($room);
        }
    }
}
