<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\RoomSession;
use App\Models\User;
use App\Notifications\SessionEndingReminder;
use App\Notifications\UpcomingClassReminder;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('roomlink:send-session-reminders
    {--upcoming-minutes=15 : Remind faculty this many minutes before a class starts}
    {--ending-minutes=10 : Remind faculty this many minutes before an active session\'s class ends}')]
#[Description('Notify faculty of upcoming classes and of sessions about to end (RLNK-30)')]
class SendSessionReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        $upcomingCount = $this->sendUpcomingClassReminders($now, (int) $this->option('upcoming-minutes'));
        $endingCount = $this->sendSessionEndingReminders($now, (int) $this->option('ending-minutes'));

        $this->info("Sent {$upcomingCount} upcoming class and {$endingCount} session ending reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Remind faculty of today's classes starting within the next $minutes,
     * unless the class was already started or already reminded today.
     */
    private function sendUpcomingClassReminders(CarbonInterface $now, int $minutes): int
    {
        $from = $now->format('H:i:s');
        $until = $this->timeOfDayAfter($now, $minutes);
        $classDate = $now->toDateString();

        $startingSoon = ClassSession::with(['faculty.user', 'subject', 'section', 'room'])
            ->where('day', ClassSession::dayCodeFor($now))
            ->where('status', 'active')
            ->get()
            ->filter(fn (ClassSession $schedule) => $schedule->start_time->format('H:i:s') > $from
                && $schedule->start_time->format('H:i:s') <= $until);

        $sent = 0;

        foreach ($startingSoon as $schedule) {
            $user = $schedule->faculty?->user;

            if (! $user) {
                continue;
            }

            $alreadyStarted = $schedule->roomSessions()
                ->where('started_at', '>=', $now->copy()->startOfDay())
                ->exists();

            $alreadyReminded = $user->notifications()
                ->where('type', UpcomingClassReminder::class)
                ->where('data->class_session_id', $schedule->id)
                ->where('data->class_date', $classDate)
                ->exists();

            if ($alreadyStarted || $alreadyReminded) {
                continue;
            }

            $user->notify(new UpcomingClassReminder($schedule, $classDate));
            $sent++;
        }

        return $sent;
    }

    /**
     * Remind faculty whose active session's scheduled class ends within the next $minutes.
     * Sessions started without a scheduled class have no end time and are skipped.
     */
    private function sendSessionEndingReminders(CarbonInterface $now, int $minutes): int
    {
        $from = $now->format('H:i:s');
        $until = $this->timeOfDayAfter($now, $minutes);

        $endingSoon = RoomSession::active()
            ->whereNotNull('class_session_id')
            ->where('started_at', '>=', $now->copy()->startOfDay())
            ->with(['faculty.user', 'room', 'classSession'])
            ->get()
            ->filter(fn (RoomSession $session) => $session->classSession
                && $session->classSession->end_time->format('H:i:s') > $from
                && $session->classSession->end_time->format('H:i:s') <= $until);

        $sent = 0;

        foreach ($endingSoon as $session) {
            $user = $session->faculty?->user;

            if (! $user || $this->wasRemindedOfEnding($user, $session)) {
                continue;
            }

            $user->notify(new SessionEndingReminder($session));
            $sent++;
        }

        return $sent;
    }

    private function wasRemindedOfEnding(User $user, RoomSession $session): bool
    {
        return $user->notifications()
            ->where('type', SessionEndingReminder::class)
            ->where('data->room_session_id', $session->id)
            ->exists();
    }

    /**
     * The time of day (H:i:s) $minutes after $now, capped at the end of the same day.
     */
    private function timeOfDayAfter(CarbonInterface $now, int $minutes): string
    {
        $later = $now->copy()->addMinutes($minutes);

        return $later->isSameDay($now) ? $later->format('H:i:s') : '23:59:59';
    }
}
