<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Campus;
use App\Models\Facility;
use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClassroomManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_one_page_shows_campuses_buildings_floors_rooms_and_facilities(): void
    {
        $campus = Campus::factory()->create(['campus_name' => 'Main Campus']);
        $building = Building::factory()->for($campus)->create(['building_name' => 'Science Hall']);
        $floor = Floor::factory()->for($building)->create(['floor_number' => 0]);
        $room = Room::factory()->for($floor)->create(['room_name' => 'COMLAB 1A']);
        $room->facilities()->attach(Facility::create(['facility_name' => 'Projector']));

        $this->actingAs($this->admin)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertSeeInOrder(['Main Campus', 'Science Hall', 'Ground Floor', 'COMLAB 1A', 'Projector'])
            ->assertSee(route('rooms.qr', $room));
    }

    public function test_the_page_opens_when_nothing_has_been_added_yet(): void
    {
        $this->actingAs($this->admin)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertSee('No campuses yet');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function oldLists(): array
    {
        return ['classrooms' => ['classrooms'], 'campus spaces' => ['campus-spaces'], 'buildings' => ['buildings'], 'floors' => ['floors'], 'rooms' => ['rooms'], 'facilities' => ['facilities']];
    }

    #[DataProvider('oldLists')]
    public function test_old_list_addresses_lead_to_the_one_page(string $list): void
    {
        $this->actingAs($this->admin)->get("/admin/{$list}")->assertRedirect(route('classrooms.index'));
    }

    public function test_only_administrators_can_open_it(): void
    {
        $this->actingAs(User::factory()->faculty()->create())->get(route('classrooms.index'))->assertForbidden();
    }

    public function test_adding_a_building_creates_its_floors(): void
    {
        $campus = Campus::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('buildings.store'), [
            'campus_id' => $campus->id,
            'building_name' => 'Science Hall',
            'number_of_floors' => 3,
        ]);

        $building = Building::sole();

        $response->assertRedirect(route('classrooms.index')."#building-{$building->id}");
        $this->assertEquals([1, 2, 3], $building->floors()->orderBy('floor_number')->pluck('floor_number')->all());
    }

    public function test_missing_floors_are_added_without_repeating_existing_ones(): void
    {
        $building = Building::factory()->create(['number_of_floors' => 3]);
        Floor::factory()->for($building)->create(['floor_number' => 2]);

        $this->actingAs($this->admin)
            ->get(route('classrooms.index'))
            ->assertSee('Add 2 missing floors');

        $this->actingAs($this->admin)
            ->post(route('buildings.floors.store', $building))
            ->assertSessionHas('success');

        $this->assertEquals([1, 2, 3], $building->floors()->orderBy('floor_number')->pluck('floor_number')->all());
    }

    public function test_several_rooms_are_added_at_once_with_the_same_details(): void
    {
        $floor = Floor::factory()->create();
        $facility = Facility::create(['facility_name' => 'Projector']);

        $this->actingAs($this->admin)
            ->post(route('rooms.store'), [
                'floor_id' => $floor->id,
                'room_name' => "COMLAB 1A\r\n\r\nCOMLAB 2A, CISCO LAB\r\ncomlab 1a",
                'capacity' => 40,
                'status' => 'available',
                'facilities' => [$facility->id],
            ])
            ->assertRedirect(route('classrooms.index')."#floor-{$floor->id}")
            ->assertSessionHas('success', '3 rooms added.');

        $this->assertSame(['CISCO LAB', 'COMLAB 1A', 'COMLAB 2A'], Room::orderBy('room_name')->pluck('room_name')->all());
        $this->assertSame(3, $facility->rooms()->where('capacity', 40)->count());
        $this->assertCount(3, Room::pluck('qr_code')->unique());
    }

    public function test_nothing_is_added_when_a_room_is_already_on_the_floor(): void
    {
        $floor = Floor::factory()->create();
        Room::factory()->for($floor)->create(['room_name' => 'COMLAB 1A']);

        $this->actingAs($this->admin)
            ->post(route('rooms.store'), ['floor_id' => $floor->id, 'room_name' => "comlab 1a\nCOMLAB 2A", 'status' => 'available'])
            ->assertSessionHasErrors(['room_name' => 'Already on this floor: comlab 1a.']);

        $this->assertSame(1, Room::count());
    }

    public function test_a_failed_form_is_shown_again_with_its_errors_and_input(): void
    {
        Campus::factory()->create(['campus_code' => 'MC']);

        $this->actingAs($this->admin)
            ->from(route('classrooms.index'))
            ->followingRedirects()
            ->post(route('campuses.store'), ['_dialog' => 'campus', 'campus_name' => 'Second </script> Campus', 'campus_code' => 'MC'])
            ->assertOk()
            ->assertSee('The campus code has already been taken.')
            ->assertSee('data-old-input', false)
            ->assertDontSee('Second </script> Campus', false);
    }

    public function test_the_edit_link_of_a_room_opens_its_form_on_the_page(): void
    {
        $room = Room::factory()->create();

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->get(route('rooms.edit', $room))
            ->assertOk()
            ->assertSee('data-open-now', false);
    }

    public function test_the_sidebar_menu_leads_to_each_campus(): void
    {
        $campuses = Campus::factory()->count(3)->create();

        $dashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $page = $this->actingAs($this->admin)->get(route('classrooms.index'))->assertOk();

        foreach ($campuses as $campus) {
            $dashboard->assertSee('href="'.route('classrooms.index').'#campus-'.$campus->id.'"', false);
            $page->assertSee('id="campus-'.$campus->id.'"', false);
        }
    }

    public function test_a_new_campus_is_shown_after_adding_it(): void
    {
        $response = $this->actingAs($this->admin)->post(route('campuses.store'), ['campus_name' => 'North Campus']);

        $response->assertRedirect(route('classrooms.index').'#campus-'.Campus::sole()->id);
    }
}
