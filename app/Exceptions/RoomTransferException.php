<?php

namespace App\Exceptions;

use App\Models\Room;
use RuntimeException;

/**
 * Thrown when a classroom session cannot be transferred to another room.
 */
class RoomTransferException extends RuntimeException
{
    public static function sessionNotActive(): self
    {
        return new self('This session has already ended and can no longer be transferred.');
    }

    public static function sameRoom(Room $room): self
    {
        return new self("Your class is already in {$room->room_name}. Choose a different room.");
    }
}
