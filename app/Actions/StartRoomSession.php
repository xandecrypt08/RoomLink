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
     * $startedVia records how the session was started (RoomSession::STARTED_VIA_*);
     * a manual start keeps the faculty member's reason for not scanning.
     *
     * @throws RoomUnavailableException when the room is under maintenance or already in use.
     */
    public function handle(
        Room $room,
        Faculty $faculty,
        ?ClassSession $classSession = null,
        string $startedVia = RoomSession::STARTED_VIA_QR,
        ?string $manualStartReason = null,
    ): RoomSession {
        try {
            return DB::transaction(function () use ($room, $faculty, $classSession, $startedVia, $manualStartReason): RoomSession {
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
                    'started_via' => $startedVia,
                    'manual_start_reason' => $startedVia === RoomSession::STARTED_VIA_MANUAL ? $manualStartReason : null,
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
