<?php

namespace Tests\Feature;

use App\Actions\StartRoomSession;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClassroomTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Monday 2026-10-12, 09:00
        $this->travelTo(Carbon::parse('2026-10-12 09:00:00'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('faculty.transfer.create'))
            ->assertRedirect(route('login'));
    }

    public function test_student_is_forbidden(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty.transfer.create'))
            ->assertForbidden();
    }

    public function test_faculty_account_without_a_faculty_record_is_forbidden(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('faculty.transfer.create'))
            ->assertForbidden();
    }

    public function test_faculty_without_an_active_session_is_told_to_start_one(): void
    {
        [$user] = $this->createFacultyMember();

        $this->actingAs($user)
            ->get(route('faculty.transfer.create'))
            ->assertOk()
            ->assertSee('You have no active session.')
            ->assertDontSee('Transfer Class');
    }

    public function test_page_lists_only_rooms_free_until_the_class_ends(): void
    {
        [$user, $faculty] = $this->createFacultyMember();
        $currentRoom = Room::factory()->create(['room_name' => 'Room 101']);
        $this->startClass($faculty, $currentRoom);

        Room::factory()->create(['room_name' => 'Room 201']);
        Room::factory()->create(['room_name' => 'Room 202', 'status' => 'maintenance']);
        $occupiedRoom = Room::factory()->create(['room_name' => 'Room 203']);
        app(StartRoomSession::class)->handle($occupiedRoom, Faculty::factory()->create());
        $bookedSoonRoom = Room::factory()->create(['room_name' => 'Room 204']);
        ClassSession::factory()->create(['room_id' => $bookedSoonRoom->id, 'start_time' => '09:30', 'end_time' => '11:00']);
        $bookedLaterRoom = Room::factory()->create(['room_name' => 'Room 205']);
        ClassSession::factory()->create(['room_id' => $bookedLaterRoom->id, 'start_time' => '10:00', 'end_time' => '12:00']);

        $response = $this->actingAs($user)->get(route('faculty.transfer.create'));

        $response->assertOk();
        $response->assertViewHas('rooms', fn ($rooms) => $rooms->pluck('room_name')->all() === ['Room 201', 'Room 205']);
    }

    public function test_transferring_moves_the_class_and_keeps_its_session_record(): void
    {
        [$user, $faculty] = $this->createFacultyMember();
        $currentRoom = Room::factory()->create(['room_name' => 'Room 101']);
        $session = $this->startClass($faculty, $currentRoom);
        $newRoom = Room::factory()->create(['room_name' => 'Room 201']);

        $this->actingAs($user)
            ->post(route('faculty.transfer.store'), [
                'room_id' => $newRoom->id,
                'reason' => 'Aircon is broken.',
            ])
            ->assertRedirect(route('faculty.transfer.create'))
            ->assertSessionHas('success', 'Class transferred from Room 101 to Room 201. Room 101 is now available.');

        $this->assertSame(RoomSession::STATUS_TRANSFERRED, $session->fresh()->status);
        $this->assertSame('Aircon is broken.', $session->fresh()->reason);
        $this->assertDatabaseHas('room_sessions', [
            'room_id' => $newRoom->id,
            'faculty_id' => $faculty->id,
            'class_session_id' => $session->class_session_id,
            'transferred_from_id' => $session->id,
            'status' => 'active',
        ]);
        $this->assertSame('available', $currentRoom->fresh()->status);
        $this->assertSame('occupied', $newRoom->fresh()->status);
    }

    public function test_transfer_to_a_room_booked_before_the_class_ends_is_rejected(): void
    {
        [$user, $faculty] = $this->createFacultyMember();
        $session = $this->startClass($faculty, Room::factory()->create());
        $bookedRoom = Room::factory()->create(['room_name' => 'Room 204']);
        $booking = ClassSession::factory()->create(['room_id' => $bookedRoom->id, 'start_time' => '09:30', 'end_time' => '11:00']);

        $this->actingAs($user)
            ->post(route('faculty.transfer.store'), ['room_id' => $bookedRoom->id])
            ->assertSessionHasErrors([
                'room_id' => "Room 204 is booked for {$booking->subject->subject_code} from 9:30 AM to 11:00 AM.",
            ]);

        $this->assertTrue($session->fresh()->isActive());
        $this->assertSame('available', $bookedRoom->fresh()->status);
    }

    public function test_transfer_to_an_occupied_room_shows_why_it_failed(): void
    {
        [$user, $faculty] = $this->createFacultyMember();
        $session = $this->startClass($faculty, Room::factory()->create());
        $occupiedRoom = Room::factory()->create(['room_name' => 'Room 203']);
        app(StartRoomSession::class)->handle($occupiedRoom, Faculty::factory()->create());

        $this->actingAs($user)
            ->post(route('faculty.transfer.store'), ['room_id' => $occupiedRoom->id])
            ->assertSessionHasErrors(['room_id' => 'Room 203 already has an active session.']);

        $this->assertTrue($session->fresh()->isActive());
    }

    public function test_transfer_without_a_room_is_rejected(): void
    {
        [$user, $faculty] = $this->createFacultyMember();
        $session = $this->startClass($faculty, Room::factory()->create());

        $this->actingAs($user)
            ->post(route('faculty.transfer.store'), [])
            ->assertSessionHasErrors(['room_id' => 'The room id field is required.']);

        $this->assertTrue($session->fresh()->isActive());
    }

    public function test_transfer_without_an_active_session_is_rejected(): void
    {
        [$user] = $this->createFacultyMember();
        $newRoom = Room::factory()->create();

        $this->actingAs($user)
            ->post(route('faculty.transfer.store'), ['room_id' => $newRoom->id])
            ->assertRedirect(route('faculty.transfer.create'))
            ->assertSessionHas('error', 'You have no active session to transfer.');

        $this->assertDatabaseCount('room_sessions', 0);
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
     * Start the faculty member's 08:00-10:00 class in the given room.
     */
    private function startClass(Faculty $faculty, Room $room): RoomSession
    {
        $schedule = ClassSession::factory()->for($faculty)->create(['room_id' => $room->id]);

        return app(StartRoomSession::class)->handle($room, $faculty, $schedule);
    }
}
