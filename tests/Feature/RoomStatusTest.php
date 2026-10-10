<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function roomPayload(Room $room, string $status): array
    {
        return [
            'floor_id' => $room->floor_id,
            'room_name' => $room->room_name,
            'capacity' => $room->capacity,
            'status' => $status,
        ];
    }

    public function test_admin_cannot_set_a_room_to_occupied(): void
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('rooms.update', $room), $this->roomPayload($room, 'occupied'));

        $response->assertSessionHasErrors('status');
        $this->assertSame('available', $room->fresh()->status);
    }

    public function test_room_with_a_running_session_stays_occupied_when_saved_as_available(): void
    {
        $room = Room::factory()->create(['status' => 'occupied']);
        RoomSession::factory()->for($room)->create();

        $this->actingAs($this->admin)
            ->put(route('rooms.update', $room), $this->roomPayload($room, 'available'))
            ->assertRedirect(route('rooms.index'));

        $this->assertSame('occupied', $room->fresh()->status);
    }

    public function test_admin_can_put_an_occupied_room_under_maintenance(): void
    {
        $room = Room::factory()->create(['status' => 'occupied']);
        RoomSession::factory()->for($room)->create();

        $this->actingAs($this->admin)
            ->put(route('rooms.update', $room), $this->roomPayload($room, 'maintenance'));

        $this->assertSame('maintenance', $room->fresh()->status);
    }

    public function test_room_left_occupied_without_a_session_is_released_when_saved(): void
    {
        $room = Room::factory()->create(['status' => 'occupied']);

        $this->actingAs($this->admin)
            ->put(route('rooms.update', $room), $this->roomPayload($room, 'available'));

        $this->assertSame('available', $room->fresh()->status);
    }

    public function test_room_with_session_history_cannot_be_deleted(): void
    {
        $room = Room::factory()->create();
        RoomSession::factory()->for($room)->ended()->create();

        $this->actingAs($this->admin)
            ->delete(route('rooms.destroy', $room))
            ->assertRedirect(route('rooms.index'))
            ->assertSessionHas('error', 'This room cannot be deleted because it has class schedules or session history.');

        $this->assertModelExists($room);
    }

    public function test_faculty_with_session_history_cannot_be_deleted(): void
    {
        $faculty = Faculty::factory()->create();
        RoomSession::factory()->for($faculty)->ended()->create();

        $this->actingAs($this->admin)
            ->delete(route('faculties.destroy', $faculty))
            ->assertRedirect(route('faculties.index'))
            ->assertSessionHas('error', 'This faculty member cannot be deleted because they have class schedules or session history.');

        $this->assertModelExists($faculty);
    }
}
