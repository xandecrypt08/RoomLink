<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\User;
use App\Notifications\SessionEndingReminder;
use App\Notifications\UpcomingClassReminder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionReminderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday, 9:50 AM
        $this->travelTo('2026-10-12 09:50:00');
    }

    public function test_faculty_is_reminded_of_a_class_starting_within_15_minutes(): void
    {
        Notification::fake();

        [$user, $faculty] = $this->createFacultyMember();

        $schedule = ClassSession::factory()->create([
            'faculty_id' => $faculty->id,
            'day' => 'M',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->artisan('roomlink:send-session-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            UpcomingClassReminder::class,
            fn (UpcomingClassReminder $notification) => $notification->classSession->is($schedule)
                && $notification->classDate === '2026-10-12'
        );
    }

    public function test_reminder_message_names_the_subject_section_room_and_time(): void
    {
        [$user, $faculty] = $this->createFacultyMember();

        $schedule = ClassSession::factory()->create([
            'faculty_id' => $faculty->id,
            'room_id' => Room::factory()->create(['room_name' => 'Room 301'])->id,
            'day' => 'M',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        $schedule->load('subject', 'section');

        $this->artisan('roomlink:send-session-reminders');

        $this->assertSame(
            "Upcoming class: {$schedule->subject->subject_code} ({$schedule->section->section_name}) starts at 10:00 AM in Room 301.",
            $user->notifications()->sole()->data['message']
        );
    }

    public function test_classes_outside_the_reminder_window_are_skipped(): void
    {
        Notification::fake();

        [, $faculty] = $this->createFacultyMember();

        // Starts in 40 minutes
        ClassSession::factory()->create(['faculty_id' => $faculty->id, 'day' => 'M', 'start_time' => '10:30:00', 'end_time' => '12:00:00']);
        // Already started
        ClassSession::factory()->create(['faculty_id' => $faculty->id, 'day' => 'M', 'start_time' => '09:00:00', 'end_time' => '09:55:00']);
        // Another day
        ClassSession::factory()->create(['faculty_id' => $faculty->id, 'day' => 'T', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);
        // Inactive
        ClassSession::factory()->create(['faculty_id' => $faculty->id, 'day' => 'M', 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'status' => 'inactive']);

        $this->artisan('roomlink:send-session-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_each_class_is_only_reminded_once_a_day(): void
    {
        [$user, $faculty] = $this->createFacultyMember();

        ClassSession::factory()->create(['faculty_id' => $faculty->id, 'day' => 'M', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);

        $this->artisan('roomlink:send-session-reminders');
        $this->travel(2)->minutes();
        $this->artisan('roomlink:send-session-reminders');

        $this->assertSame(1, $user->notifications()->where('type', UpcomingClassReminder::class)->count());
    }

    public function test_the_same_weekly_class_is_reminded_again_next_week(): void
    {
        [$user, $faculty] = $this->createFacultyMember();

        ClassSession::factory()->create(['faculty_id' => $faculty->id, 'day' => 'M', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);

        $this->artisan('roomlink:send-session-reminders');
        $this->travel(7)->days();
        $this->artisan('roomlink:send-session-reminders');

        $this->assertSame(2, $user->notifications()->where('type', UpcomingClassReminder::class)->count());
    }

    public function test_a_class_started_early_is_not_reminded(): void
    {
        Notification::fake();

        [, $faculty] = $this->createFacultyMember();

        $schedule = ClassSession::factory()->create(['faculty_id' => $faculty->id, 'day' => 'M', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);

        RoomSession::factory()->create([
            'room_id' => $schedule->room_id,
            'faculty_id' => $faculty->id,
            'class_session_id' => $schedule->id,
            'started_at' => now()->subMinutes(5),
        ]);

        $this->artisan('roomlink:send-session-reminders')->assertSuccessful();

        Notification::assertNotSentTo($faculty->user, UpcomingClassReminder::class);
    }

    public function test_faculty_without_a_login_account_is_skipped(): void
    {
        Notification::fake();

        $unlinkedFaculty = Faculty::factory()->create();

        ClassSession::factory()->create(['faculty_id' => $unlinkedFaculty->id, 'day' => 'M', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);

        $this->artisan('roomlink:send-session-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_faculty_is_reminded_when_their_active_session_ends_within_10_minutes(): void
    {
        Notification::fake();

        [$user, $faculty] = $this->createFacultyMember();

        $session = $this->startRoomSession($faculty, start: '08:00:00', end: '10:00:00');

        $this->artisan('roomlink:send-session-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            SessionEndingReminder::class,
            fn (SessionEndingReminder $notification) => $notification->roomSession->is($session)
        );
    }

    public function test_session_ending_message_names_the_room_and_end_time(): void
    {
        [$user, $faculty] = $this->createFacultyMember();

        $this->startRoomSession($faculty, start: '08:00:00', end: '10:00:00', roomName: 'Room 205');

        $this->artisan('roomlink:send-session-reminders');

        $this->assertSame(
            'Your class in Room 205 ends at 10:00 AM. Remember to end your session to release the room.',
            $user->notifications()->sole()->data['message']
        );
    }

    public function test_session_ending_is_only_reminded_once(): void
    {
        [$user, $faculty] = $this->createFacultyMember();

        $this->startRoomSession($faculty, start: '08:00:00', end: '10:00:00');

        $this->artisan('roomlink:send-session-reminders');
        $this->travel(2)->minutes();
        $this->artisan('roomlink:send-session-reminders');

        $this->assertSame(1, $user->notifications()->where('type', SessionEndingReminder::class)->count());
    }

    public function test_sessions_not_ending_soon_ended_sessions_and_unscheduled_sessions_are_skipped(): void
    {
        Notification::fake();

        [, $faculty] = $this->createFacultyMember();
        [, $otherFaculty] = $this->createFacultyMember();
        [, $thirdFaculty] = $this->createFacultyMember();

        // Ends at 11:00, an hour away
        $this->startRoomSession($faculty, start: '08:00:00', end: '11:00:00');

        // Already ended
        $this->startRoomSession($otherFaculty, start: '08:00:00', end: '10:00:00')
            ->update(['status' => RoomSession::STATUS_ENDED, 'ended_at' => now()]);

        // Temporary session with no scheduled class, so no end time
        RoomSession::factory()->create(['faculty_id' => $thirdFaculty->id, 'started_at' => now()->subHour()]);

        $this->artisan('roomlink:send-session-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    /**
     * Create a faculty login account and its linked Faculty record.
     *
     * @return array{0: User, 1: Faculty}
     */
    private function createFacultyMember(): array
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();

        return [$user, $faculty];
    }

    /**
     * Start an active room session for a class scheduled today.
     */
    private function startRoomSession(Faculty $faculty, string $start, string $end, ?string $roomName = null): RoomSession
    {
        $room = Room::factory()->create($roomName ? ['room_name' => $roomName] : []);

        $schedule = ClassSession::factory()->create([
            'room_id' => $room->id,
            'faculty_id' => $faculty->id,
            'day' => 'M',
            'start_time' => $start,
            'end_time' => $end,
        ]);

        return RoomSession::factory()->create([
            'room_id' => $room->id,
            'faculty_id' => $faculty->id,
            'class_session_id' => $schedule->id,
            'started_at' => now()->setTimeFromTimeString($start),
        ]);
    }
}
