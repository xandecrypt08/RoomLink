<?php

namespace App\Actions;

use App\Exceptions\RoomTransferException;
use App\Exceptions\RoomUnavailableException;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Support\Facades\DB;

class TransferRoomSession
{
    public function __construct(
        public EndRoomSession $endRoomSession,
        public StartRoomSession $startRoomSession,
    ) {}

    /**
     * Move an active session to another room. The current session is closed as
     * transferred and a new session continues the same class in the new room.
     * If the new room cannot be used, nothing changes.
     *
     * @return RoomSession The new session in the new room.
     *
     * @throws RoomTransferException when the session has ended or the room is the same.
     * @throws RoomUnavailableException when the new room is under maintenance or in use.
     */
    public function handle(RoomSession $session, Room $newRoom, ?string $reason = null): RoomSession
    {
        if ($session->room_id === $newRoom->id) {
            throw RoomTransferException::sameRoom($newRoom);
        }

        return DB::transaction(function () use ($session, $newRoom, $reason): RoomSession {
            $lockedSession = RoomSession::query()->lockForUpdate()->findOrFail($session->id);

            if (! $lockedSession->isActive()) {
                throw RoomTransferException::sessionNotActive();
            }

            $closedSession = $this->endRoomSession->handle($lockedSession, RoomSession::STATUS_TRANSFERRED, $reason);

            $newSession = $this->startRoomSession->handle(
                $newRoom,
                $closedSession->faculty,
                $closedSession->classSession,
                RoomSession::STARTED_VIA_TRANSFER,
            );

            $newSession->update(['transferred_from_id' => $closedSession->id]);

            return $newSession;
        });
    }
}
