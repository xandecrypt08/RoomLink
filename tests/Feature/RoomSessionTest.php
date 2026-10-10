<?php

namespace Tests\Feature;

use App\Actions\EndRoomSession;
use App\Actions\StartRoomSession;
use App\Exceptions\RoomUnavailableException;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoomSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_a_session_logs_it_and_marks_the_room_occupied(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 08:05:00'));
        $room = Room::factory()->create();
        $schedule = ClassSession::factory()->create(['room_id' => $room->id]);

        $session = app(StartRoomSession::class)->handle($room, $schedule->faculty, $schedule);

        $this->assertDatabaseHas('room_sessions', [
            'id' => $session->id,
            'room_id' => $room->id,
            'faculty_id' => $schedule->faculty_id,
            'class_session_id' => $schedule->id,
            'status' => 'active',
            'started_at' => '2026-10-12 08:05:00',
            'ended_at' => null,
        ]);
        $this->assertSame('occupied', $room->fresh()->status);
        $this->assertTrue($room->activeSession->is($session));
    }

    public function test_temporary_session_has_no_schedule(): void
    {
        $session = app(StartRoomSession::class)->handle(Room::factory()->create(), Faculty::factory()->create());

        $this->assertNull($session->class_session_id);
        $this->assertTrue($session->isActive());
    }

    public function test_session_cannot_start_in_an_occupied_room(): void
    {
        $room = Room::factory()->create();
        app(StartRoomSession::class)->handle($room, Faculty::factory()->create());

        $this->expectException(RoomUnavailableException::class);
        $this->expectExceptionMessage("{$room->room_name} already has an active session.");

        app(StartRoomSession::class)->handle($room, Faculty::factory()->create());
    }

    public function test_session_cannot_start_in_a_room_under_maintenance(): void
    {
        $room = Room::factory()->create(['status' => 'maintenance']);

        try {
            app(StartRoomSession::class)->handle($room, Faculty::factory()->create());
            $this->fail('Expected RoomUnavailableException.');
        } catch (RoomUnavailableException $exception) {
            $this->assertSame("{$room->room_name} is under maintenance and cannot be used right now.", $exception->getMessage());
        }

        $this->assertDatabaseCount('room_sessions', 0);
        $this->assertSame('maintenance', $room->fresh()->status);
    }

    public function test_database_allows_only_one_active_session_per_room(): void
    {
        $room = Room::factory()->create();
        RoomSession::factory()->for($room)->create();

        $this->expectException(UniqueConstraintViolationException::class);

        RoomSession::factory()->for($room)->create();
    }

    public function test_a_room_can_have_many_finished_sessions_and_one_active(): void
    {
        $room = Room::factory()->create();
        RoomSession::factory()->for($room)->ended()->count(2)->create();
        $active = RoomSession::factory()->for($room)->create();

        $this->assertCount(3, $room->roomSessions);
        $this->assertTrue($room->activeSession->is($active));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function closingStatuses(): array
    {
        return [
            'ended' => [RoomSession::STATUS_ENDED],
            'transferred' => [RoomSession::STATUS_TRANSFERRED],
            'not held' => [RoomSession::STATUS_NOT_HELD],
        ];
    }

    #[DataProvider('closingStatuses')]
    public function test_closing_a_session_records_it_and_frees_the_room(string $status): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 08:00:00'));
        $room = Room::factory()->create();
        $session = app(StartRoomSession::class)->handle($room, Faculty::factory()->create());

        $this->travelTo(Carbon::parse('2026-10-12 09:30:00'));
        app(EndRoomSession::class)->handle($session, $status, 'Some reason');

        $this->assertDatabaseHas('room_sessions', [
            'id' => $session->id,
            'status' => $status,
            'ended_at' => '2026-10-12 09:30:00',
            'reason' => 'Some reason',
            'active_room_id' => null,
        ]);
        $this->assertSame('available', $room->fresh()->status);
        $this->assertNull($room->fresh()->activeSession);
    }

    public function test_room_can_be_used_again_after_the_session_ends(): void
    {
        $room = Room::factory()->create();
        $first = app(StartRoomSession::class)->handle($room, Faculty::factory()->create());
        app(EndRoomSession::class)->handle($first);

        $second = app(StartRoomSession::class)->handle($room, Faculty::factory()->create());

        $this->assertTrue($room->fresh()->activeSession->is($second));
    }

    public function test_ending_keeps_maintenance_set_during_the_session(): void
    {
        $room = Room::factory()->create();
        $session = app(StartRoomSession::class)->handle($room, Faculty::factory()->create());
        $room->update(['status' => 'maintenance']);

        app(EndRoomSession::class)->handle($session);

        $this->assertSame('maintenance', $room->fresh()->status);
    }

    public function test_ending_an_already_closed_session_changes_nothing(): void
    {
        $session = RoomSession::factory()->ended()->create(['reason' => null]);
        $endedAt = $session->ended_at;

        app(EndRoomSession::class)->handle($session, RoomSession::STATUS_NOT_HELD, 'Late');

        $session->refresh();
        $this->assertSame(RoomSession::STATUS_ENDED, $session->status);
        $this->assertTrue($session->ended_at->equalTo($endedAt));
        $this->assertNull($session->reason);
    }

    public function test_closing_with_an_invalid_status_is_rejected(): void
    {
        $session = RoomSession::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(EndRoomSession::class)->handle($session, RoomSession::STATUS_ACTIVE);
    }
}
