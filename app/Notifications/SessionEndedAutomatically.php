<?php

namespace App\Notifications;

use App\Models\RoomSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells a faculty member that a session they forgot was ended for them.
 */
class SessionEndedAutomatically extends Notification
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
     * @return array{room_session_id: int, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $session = $this->roomSession->loadMissing('room');

        return [
            'room_session_id' => $session->id,
            'message' => "Your session in {$session->room->room_name} was still running, so it was ended automatically as of {$session->ended_at->format('M j, g:i A')} and the room was released.",
        ];
    }
}
