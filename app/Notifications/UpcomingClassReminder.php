<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UpcomingClassReminder extends Notification
{
    use Queueable;

    /**
     * @param  string  $classDate  The date of the class (Y-m-d), used to send one reminder per class per day.
     */
    public function __construct(public ClassSession $classSession, public string $classDate) {}

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
     * @return array{class_session_id: int, class_date: string, subject_code: string, section_name: string, room_name: string, start_time: string, end_time: string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $classSession = $this->classSession->loadMissing(['subject', 'section', 'room']);

        $startTime = $classSession->start_time->format('g:i A');

        return [
            'class_session_id' => $classSession->id,
            'class_date' => $this->classDate,
            'subject_code' => $classSession->subject->subject_code,
            'section_name' => $classSession->section->section_name,
            'room_name' => $classSession->room->room_name,
            'start_time' => $startTime,
            'end_time' => $classSession->end_time->format('g:i A'),
            'message' => "Upcoming class: {$classSession->subject->subject_code} ({$classSession->section->section_name}) starts at {$startTime} in {$classSession->room->room_name}.",
        ];
    }
}
