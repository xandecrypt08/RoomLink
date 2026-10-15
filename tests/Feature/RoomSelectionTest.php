<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomSelectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->user = User::factory()->faculty()->create();
        Faculty::factory()->for($this->user)->create();
    }

    public function test_room_list_links_each_room_to_its_verification_page(): void
    {
        $room = Room::factory()->create(['room_name' => 'ACAD 101']);

        $this->actingAs($this->user)
            ->get(route('faculty.rooms.index'))
            ->assertOk()
            ->assertSee('ACAD 101')
            ->assertSee(route('faculty.sessions.scan', $room->qr_code));
    }

    public function test_search_matches_room_name_and_building(): void
    {
        $science = Floor::factory()->for(Building::factory()->state(['building_name' => 'Science Hall']))->create();
        Room::factory()->for($science)->create(['room_name' => 'LAB 1']);
        Room::factory()->create(['room_name' => 'ACAD 101']);

        $this->actingAs($this->user)->get(route('faculty.rooms.index', ['search' => 'science']))
            ->assertSee('LAB 1')
            ->assertDontSee('ACAD 101');

        $this->actingAs($this->user)->get(route('faculty.rooms.index', ['search' => 'ACAD']))
            ->assertSee('ACAD 101')
            ->assertDontSee('LAB 1');
    }

    public function test_selecting_a_room_starts_a_session_like_a_qr_scan(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 08:05:00'));
        $room = Room::factory()->create();
        ClassSession::factory()->for($this->user->faculty)->create(['room_id' => $room->id]);

        $this->actingAs($this->user)
            ->get(route('faculty.sessions.scan', $room->qr_code))
            ->assertSee('Start Session');

        $this->actingAs($this->user)
            ->post(route('faculty.sessions.store'), ['code' => $room->qr_code])
            ->assertRedirect(route('faculty.dashboard'));

        $this->assertSame('occupied', $room->fresh()->status);
    }

    public function test_unknown_qr_code_offers_manual_selection(): void
    {
        $this->actingAs($this->user)
            ->get(route('faculty.sessions.scan', 'ROOM-DOESNOTEXIST'))
            ->assertSee('Find the Room Manually')
            ->assertSee(route('faculty.rooms.index'));
    }

    public function test_room_scheduled_for_someone_else_offers_a_temporary_request(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 08:05:00'));
        $room = Room::factory()->create();
        ClassSession::factory()->create(['room_id' => $room->id]);

        $this->actingAs($this->user)
            ->get(route('faculty.sessions.scan', $room->qr_code))
            ->assertSee('Request This Room Temporarily')
            ->assertSee(route('faculty.temporary-requests.create', ['code' => $room->qr_code]), false);
    }

    public function test_students_cannot_open_the_room_finder(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty.rooms.index'))
            ->assertForbidden();
    }

    public function test_backfill_migration_gives_rooms_without_a_qr_code_a_unique_code(): void
    {
        $withoutCode = Room::factory()->create();
        $withCode = Room::factory()->create();
        DB::table('rooms')->where('id', $withoutCode->id)->update(['qr_code' => null]);
        $existingCode = $withCode->qr_code;

        $migration = require database_path('migrations/2026_10_15_133657_backfill_missing_room_qr_codes.php');
        $migration->up();

        $this->assertMatchesRegularExpression('/^ROOM-[A-Z0-9]{10}$/', $withoutCode->fresh()->qr_code);
        $this->assertSame($existingCode, $withCode->fresh()->qr_code);
    }
}
