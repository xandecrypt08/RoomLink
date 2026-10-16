<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Campus;
use App\Models\ClassSession;
use App\Models\Facility;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SafeDeleteTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_campus_whose_rooms_have_session_history_is_refused_instead_of_crashing(): void
    {
        $room = Room::factory()->create();
        RoomSession::factory()->create(['room_id' => $room->id, 'status' => RoomSession::STATUS_ENDED, 'ended_at' => now()]);
        $campus = $room->floor->building->campus;

        $this->actingAs($this->admin)
            ->delete(route('campuses.destroy', $campus))
            ->assertRedirect(route('campuses.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($campus);
        $this->assertModelExists($room);
    }

    public function test_campus_with_buildings_is_refused_and_the_reason_is_shown(): void
    {
        $campus = Building::factory()->create()->campus;

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->delete(route('campuses.destroy', $campus))
            ->assertOk()
            ->assertSee('This campus cannot be deleted because it still has buildings.');

        $this->assertModelExists($campus);
    }

    public function test_empty_campus_is_deleted(): void
    {
        $campus = Campus::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('campuses.destroy', $campus))
            ->assertSessionHas('success');

        $this->assertModelMissing($campus);
    }

    public function test_building_with_floors_is_refused_and_the_reason_is_shown(): void
    {
        $building = Floor::factory()->create()->building;

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->delete(route('buildings.destroy', $building))
            ->assertOk()
            ->assertSee('This building cannot be deleted because it still has floors.');

        $this->assertModelExists($building);
    }

    public function test_empty_building_is_deleted(): void
    {
        $building = Building::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('buildings.destroy', $building))
            ->assertSessionHas('success');

        $this->assertModelMissing($building);
    }

    public function test_class_session_with_history_is_refused_and_the_reason_is_shown(): void
    {
        $schedule = ClassSession::factory()->create();
        RoomSession::factory()->create([
            'room_id' => $schedule->room_id,
            'faculty_id' => $schedule->faculty_id,
            'class_session_id' => $schedule->id,
            'status' => RoomSession::STATUS_ENDED,
            'ended_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->delete(route('schedules.destroy', $schedule))
            ->assertOk()
            ->assertSee('Set its status to Inactive instead.');

        $this->assertModelExists($schedule);
    }

    public function test_class_session_without_history_is_deleted(): void
    {
        $schedule = ClassSession::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('schedules.destroy', $schedule))
            ->assertSessionHas('success');

        $this->assertModelMissing($schedule);
    }

    public function test_facility_used_by_rooms_is_deleted_and_removed_from_them(): void
    {
        $room = Room::factory()->create();
        $facility = Facility::create(['facility_name' => 'Projector']);
        $room->facilities()->attach($facility);

        $this->actingAs($this->admin)
            ->delete(route('facilities.destroy', $facility))
            ->assertSessionHas('success');

        $this->assertModelMissing($facility);
        $this->assertSame(0, $room->facilities()->count());
    }
}
