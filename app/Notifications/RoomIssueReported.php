<?php

namespace App\Notifications;

use App\Models\RoomIssue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells administrators about a newly reported room issue.
 */
class RoomIssueReported extends Notification
{
    use Queueable;

    public function __construct(public RoomIssue $issue) {}

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
     * @return array{room_issue_id: int, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $issue = $this->issue->loadMissing(['room', 'reporter']);
        $message = "{$issue->reporter->full_name} reported an issue in {$issue->room->room_name}: {$issue->category_label}.";

        return [
            'room_issue_id' => $issue->id,
            'message' => $issue->qr_replacement_requested ? $message.' A replacement QR code was requested.' : $message,
        ];
    }
}
