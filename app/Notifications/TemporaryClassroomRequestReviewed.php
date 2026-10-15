<?php

namespace App\Notifications;

use App\Models\TemporaryClassroomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TemporaryClassroomRequestReviewed extends Notification
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
     * @return array{temporary_classroom_request_id: int, room_name: string, status: string, decision_note: ?string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $request = $this->temporaryClassroomRequest->loadMissing('room');

        $message = "Your request to use {$request->room->room_name} was {$request->status}.";

        if ($request->status === 'approved') {
            $message .= ' Scan the room to start your session.';
        }

        if (filled($request->decision_note)) {
            $message .= " Note: {$request->decision_note}";
        }

        return [
            'temporary_classroom_request_id' => $request->id,
            'room_name' => $request->room->room_name,
            'status' => $request->status,
            'decision_note' => $request->decision_note,
            'message' => $message,
        ];
    }
}
