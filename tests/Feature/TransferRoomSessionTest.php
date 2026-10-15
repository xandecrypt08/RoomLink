<?php

namespace Tests\Feature;

use App\Actions\EndRoomSession;
use App\Actions\StartRoomSession;
use App\Actions\TransferRoomSession;
use App\Exceptions\RoomTransferException;
use App\Exceptions\RoomUnavailableException;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransferRoomSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 2026-10-12, 09:00
        $this->travelTo(Carbon::parse('2026-10-12 09:00:00'));
    }

    public function test_transfer_closes_the_old_session_and_continues_the_class_in_the_new_room(): void
    {
        [$session, $oldRoom, $schedule] = $this->startScheduledClass();
        $newRoom = Room::factory()->create();

        $newSession = app(TransferRoomSession::class)->handle($session, $newRoom, 'Projector stopped working.');

        $this->assertDatabaseHas('room_sessions', [
            'id' => $session->id,
            'room_id' => $oldRoom->id,
            'status' => 'transferred',
            'ended_at' => '2026-10-12 09:00:00',
            'reason' => 'Projector stopped working.',
        ]);
        $this->assertDatabaseHas('room_sessions', [
            'id' => $newSession->id,
            'room_id' => $newRoom->id,
            'faculty_id' => $schedule->faculty_id,
            'class_session_id' => $schedule->id,
            'transferred_from_id' => $session->id,
            'status' => 'active',
            'started_at' => '2026-10-12 09:00:00',
        ]);
        $this->assertSame('available', $oldRoom->fresh()->status);
        $this->assertSame('occupied', $newRoom->fresh()->status);
        $this->assertTrue($newSession->transferredFrom->is($session));
    }

    public function test_transfer_to_an_occupied_room_changes_nothing(): void
    {
        [$session, $oldRoom] = $this->startScheduledClass();
        $occupiedRoom = Room::factory()->create();
        app(StartRoomSession::class)->handle($occupiedRoom, Faculty::factory()->create());

        try {
            app(TransferRoomSession::class)->handle($session, $occupiedRoom);
            $this->fail('Expected RoomUnavailableException.');
        } catch (RoomUnavailableException $exception) {
            $this->assertSame("{$occupiedRoom->room_name} already has an active session.", $exception->getMessage());
        }

        $this->assertTrue($session->fresh()->isActive());
        $this->assertSame('occupied', $oldRoom->fresh()->status);
        $this->assertDatabaseCount('room_sessions', 2);
    }

    public function test_transfer_to_a_room_under_maintenance_changes_nothing(): void
    {
        [$session, $oldRoom] = $this->startScheduledClass();
        $maintenanceRoom = Room::factory()->create(['status' => 'maintenance']);

        try {
            app(TransferRoomSession::class)->handle($session, $maintenanceRoom);
            $this->fail('Expected RoomUnavailableException.');
        } catch (RoomUnavailableException $exception) {
            $this->assertSame("{$maintenanceRoom->room_name} is under maintenance and cannot be used right now.", $exception->getMessage());
        }

        $this->assertTrue($session->fresh()->isActive());
        $this->assertSame('occupied', $oldRoom->fresh()->status);
        $this->assertSame('maintenance', $maintenanceRoom->fresh()->status);
        $this->assertDatabaseCount('room_sessions', 1);
    }

    public function test_transfer_to_the_same_room_is_rejected(): void
    {
        [$session, $oldRoom] = $this->startScheduledClass();

        $this->expectException(RoomTransferException::class);
        $this->expectExceptionMessage("Your class is already in {$oldRoom->room_name}. Choose a different room.");

        app(TransferRoomSession::class)->handle($session, $oldRoom);
    }

    public function test_an_ended_session_cannot_be_transferred(): void
    {
        [$session] = $this->startScheduledClass();
        app(EndRoomSession::class)->handle($session);
        $newRoom = Room::factory()->create();

        try {
            app(TransferRoomSession::class)->handle($session, $newRoom);
            $this->fail('Expected RoomTransferException.');
        } catch (RoomTransferException $exception) {
            $this->assertSame('This session has already ended and can no longer be transferred.', $exception->getMessage());
        }

        $this->assertSame('ended', $session->fresh()->status);
        $this->assertSame('available', $newRoom->fresh()->status);
        $this->assertDatabaseCount('room_sessions', 1);
    }

    /**
     * Start today's 08:00-10:00 class in its scheduled room.
     *
     * @return array{0: RoomSession, 1: Room, 2: ClassSession}
     */
    private function startScheduledClass(): array
    {
        $room = Room::factory()->create();
        $schedule = ClassSession::factory()->create(['room_id' => $room->id]);

        $session = app(StartRoomSession::class)->handle($room, $schedule->faculty, $schedule);

        return [$session, $room, $schedule];
    }
}
