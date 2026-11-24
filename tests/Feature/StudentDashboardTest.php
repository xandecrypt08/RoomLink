<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-23 09:00')); // a Monday
    }

    public function test_rooms_show_whether_they_are_free_and_until_when(): void
    {
        $free = Room::factory()->create();
        $later = Room::factory()->create();
        ClassSession::factory()->create(['room_id' => $later->id, 'day' => 'M', 'start_time' => '13:00:00', 'end_time' => '15:00:00']);
        $now = ClassSession::factory()->create(['day' => 'M', 'start_time' => '08:00:00', 'end_time' => '10:00:00']);
        $occupied = Room::factory()->create(['status' => 'occupied']);

        $this->actingAs(User::factory()->student()->create())
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('availability', fn ($availability) => $availability->all() == [
                $free->id => ['free' => true, 'note' => 'Free for the rest of today'],
                $later->id => ['free' => true, 'note' => 'Free until 1:00 PM'],
                $now->room_id => ['free' => false, 'note' => "{$now->subject->subject_code} scheduled until 10:00 AM"],
                $occupied->id => ['free' => false, 'note' => null],
            ])
            ->assertSee('Free until 1:00 PM')
            ->assertSee(route('timetable', ['type' => 'room', 'id' => $free->id]));
    }

    public function test_rooms_can_be_filtered_by_campus(): void
    {
        $room = Room::factory()->create();
        $campus = $room->floor->building->campus;

        $this->actingAs(User::factory()->student()->create())
            ->get(route('student.dashboard'))
            ->assertSee('<option value="'.$campus->id.'">', false)
            ->assertSee('data-campus="'.$campus->id.'"', false);
    }

    public function test_the_app_can_be_added_to_a_home_screen(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.json')), true);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path($icon['src']));
            $this->assertSame($icon['sizes'], implode('x', array_slice(getimagesize(public_path($icon['src'])), 0, 2)));
        }

        $this->get(route('login'))->assertSee('rel="manifest"', false);
    }
}
