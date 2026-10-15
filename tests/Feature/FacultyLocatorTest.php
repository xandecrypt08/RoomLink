<?php

namespace Tests\Feature;

use App\Actions\EndRoomSession;
use App\Actions\StartRoomSession;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FacultyLocatorTest extends TestCase
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
        $this->get(route('faculty-locator.index'))
            ->assertRedirect(route('login'));
    }

    #[TestWith(['admin'])]
    #[TestWith(['faculty'])]
    #[TestWith(['student'])]
    public function test_every_signed_in_role_can_open_the_locator(string $role): void
    {
        $this->actingAs(User::factory()->{$role}()->create())
            ->get(route('faculty-locator.index'))
            ->assertOk();
    }

    public function test_shows_where_a_faculty_member_is_teaching_with_subject_section_and_schedule(): void
    {
        $faculty = Faculty::factory()->create(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $this->startScheduledClass($faculty, Room::factory()->create(['room_name' => 'Room 301']), [
            'subject_id' => Subject::factory()->create(['subject_code' => 'IT 213', 'subject_name' => 'Data Structures']),
            'section_id' => Section::factory()->create(['section_name' => 'BSIT 2-A']),
        ]);

        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty-locator.index'))
            ->assertOk()
            ->assertSeeInOrder(['Maria Santos', 'IT 213', 'Data Structures', 'BSIT 2-A', 'Room 301', '8:00 AM', '10:00 AM']);
    }

    public function test_faculty_member_disappears_from_the_locator_once_their_session_ends(): void
    {
        $faculty = Faculty::factory()->create(['last_name' => 'Santos']);
        $session = $this->startScheduledClass($faculty, Room::factory()->create());
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('faculty-locator.index'))
            ->assertSee('Santos');

        app(EndRoomSession::class)->handle($session);

        $this->actingAs($student)
            ->get(route('faculty-locator.index'))
            ->assertDontSee('Santos')
            ->assertSee('No faculty members are holding a class right now.');
    }

    public function test_session_left_running_after_its_scheduled_class_ends_is_hidden(): void
    {
        $faculty = Faculty::factory()->create(['last_name' => 'Santos']);
        $this->startScheduledClass($faculty, Room::factory()->create());

        $this->travelTo(Carbon::parse('2026-10-12 10:00:00'));

        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty-locator.index'))
            ->assertViewHas('locatedSessions', fn ($sessions) => $sessions->isEmpty())
            ->assertDontSee('Santos');
    }

    public function test_temporary_session_left_running_from_a_previous_day_is_hidden(): void
    {
        RoomSession::factory()->create(['started_at' => Carbon::parse('2026-10-11 15:00:00')]);

        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty-locator.index'))
            ->assertViewHas('locatedSessions', fn ($sessions) => $sessions->isEmpty());
    }

    public function test_temporary_session_is_listed_with_the_time_it_started(): void
    {
        $faculty = Faculty::factory()->create(['last_name' => 'Santos']);
        $this->travelTo(Carbon::parse('2026-10-12 08:45:00'));
        app(StartRoomSession::class)->handle(Room::factory()->create(['room_name' => 'Room 105']), $faculty);
        $this->travelTo(Carbon::parse('2026-10-12 09:00:00'));

        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty-locator.index'))
            ->assertSeeInOrder(['Santos', 'Temporary Session', 'Room 105', 'Since 8:45 AM']);
    }

    public function test_faculty_members_are_listed_by_last_name(): void
    {
        $this->startScheduledClass(Faculty::factory()->create(['last_name' => 'Villanueva']), Room::factory()->create());
        $this->startScheduledClass(Faculty::factory()->create(['last_name' => 'Aquino']), Room::factory()->create());

        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty-locator.index'))
            ->assertSeeInOrder(['Aquino', 'Villanueva']);
    }

    public function test_faculty_names_are_escaped(): void
    {
        $faculty = Faculty::factory()->create(['last_name' => '<script>alert(1)</script>']);
        $this->startScheduledClass($faculty, Room::factory()->create());

        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty-locator.index'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    /**
     * Start the faculty member's Monday 08:00-10:00 class in the given room.
     *
     * @param  array<string, mixed>  $scheduleAttributes
     */
    private function startScheduledClass(Faculty $faculty, Room $room, array $scheduleAttributes = []): RoomSession
    {
        $schedule = ClassSession::factory()->for($faculty)->create(['room_id' => $room->id, ...$scheduleAttributes]);

        return app(StartRoomSession::class)->handle($room, $faculty, $schedule);
    }
}
