<?php

namespace App\Notifications;

use App\Models\RoomSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionEndingReminder extends Notification
{
    use Queueable;

    public function __construct(public RoomSession $roomSession) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array{room_session_id: int, room_name: string, end_time: string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $roomSession = $this->roomSession->loadMissing(['room', 'classSession']);

        $endTime = $roomSession->classSession->end_time->format('g:i A');

        return [
            'room_session_id' => $roomSession->id,
            'room_name' => $roomSession->room->room_name,
            'end_time' => $endTime,
            'message' => "Your class in {$roomSession->room->room_name} ends at {$endTime}. Remember to end your session to release the room.",
        ];
    }
}
