<?php

namespace App\Exceptions;

use App\Models\Room;
use RuntimeException;

/**
 * Thrown when a session cannot start in a room because the room is
 * under maintenance or already has an active session.
 */
class RoomUnavailableException extends RuntimeException
{
    public static function underMaintenance(Room $room): self
    {
        return new self("{$room->room_name} is under maintenance and cannot be used right now.");
    }

    public static function occupied(Room $room): self
    {
        return new self("{$room->room_name} already has an active session.");
    }
}
