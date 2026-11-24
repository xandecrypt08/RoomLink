<?php

namespace App\Notifications;

use App\Models\RoomIssue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells the faculty member who reported an issue that its status changed.
 */
class RoomIssueUpdated extends Notification
{
    use Queueable;

    public function __construct(public RoomIssue $issue, public ?string $note = null) {}

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
        $issue = $this->issue->loadMissing('room');

        return [
            'room_issue_id' => $issue->id,
            'message' => "Your report about {$issue->room->room_name} ({$issue->category_label}) is now {$issue->status_label}."
                .($this->note ? " Note: {$this->note}" : ''),
        ];
    }
}
