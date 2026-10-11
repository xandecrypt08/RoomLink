<?php

namespace App\Notifications;

use App\Models\TemporaryClassroomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TemporaryClassroomRequested extends Notification
{
    use Queueable;

    public function __construct(public TemporaryClassroomRequest $temporaryClassroomRequest) {}

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
     * @return array{temporary_classroom_request_id: int, requester_name: string, room_name: string, reason: string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $request = $this->temporaryClassroomRequest->loadMissing(['requester', 'room']);

        return [
            'temporary_classroom_request_id' => $request->id,
            'requester_name' => $request->requester->full_name,
            'room_name' => $request->room->room_name,
            'reason' => $request->reason,
            'message' => "{$request->requester->full_name} is requesting temporary use of {$request->room->room_name}.",
        ];
    }
}
