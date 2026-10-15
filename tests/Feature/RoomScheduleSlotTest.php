<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomScheduleSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_page_links_to_add_and_edit_its_schedule_slots(): void
    {
        $this->withoutVite();
        $room = Room::factory()->create();
        $slot = ClassSession::factory()->create(['room_id' => $room->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee(route('schedules.create', ['room_id' => $room->id]), false)
            ->assertSee(route('schedules.edit', $slot));
    }

    public function test_room_page_lists_schedule_slots_from_monday_by_start_time(): void
    {
        $this->withoutVite();
        $room = Room::factory()->create();
        ClassSession::factory()->create(['room_id' => $room->id, 'day' => 'T', 'start_time' => '07:00:00', 'end_time' => '08:00:00']);
        ClassSession::factory()->create(['room_id' => $room->id, 'day' => 'M', 'start_time' => '13:00:00', 'end_time' => '14:00:00']);
        ClassSession::factory()->create(['room_id' => $room->id, 'day' => 'M', 'start_time' => '09:00:00', 'end_time' => '10:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSeeInOrder(['9:00 AM', '1:00 PM', 'Tuesday', '7:00 AM']);
    }

    public function test_schedule_form_preselects_the_room_from_the_link(): void
    {
        $this->withoutVite();
        Room::factory()->create();
        $room = Room::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('schedules.create', ['room_id' => $room->id]));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/value="'.$room->id.'"\s*selected/',
            $response->getContent()
        );
    }
}
