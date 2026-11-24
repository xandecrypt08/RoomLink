<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicTermTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private AcademicTerm $current;

    private AcademicTerm $next;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-23 08:30')); // a Monday
        $this->admin = User::factory()->admin()->create();
        $this->current = AcademicTerm::current();
        $this->next = AcademicTerm::create(['school_year' => 2026, 'semester' => 2]);
    }

    /**
     * @return array<string, array{string, int, int}>
     */
    public static function dates(): array
    {
        return [
            'August starts a school year' => ['2026-08-01', 2026, 1],
            'December is first semester' => ['2026-12-15', 2026, 1],
            'January is second semester' => ['2027-01-10', 2026, 2],
            'June is summer' => ['2027-06-10', 2026, 3],
        ];
    }

    #[DataProvider('dates')]
    public function test_the_current_term_follows_the_date_until_one_is_set(string $date, int $schoolYear, int $semester): void
    {
        AcademicTerm::query()->delete();
        $this->travelTo(Carbon::parse($date));

        $this->assertSame([$schoolYear, $semester], [AcademicTerm::current()->school_year, AcademicTerm::current()->semester]);
    }

    public function test_the_dashboard_shows_the_term_as_a_button_with_the_other_terms(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('First Semester, S.Y. 2026–2027')
            ->assertSee('value="2027-1"', false);
    }

    public function test_another_semester_starts_blank_and_keeps_the_shared_records(): void
    {
        $class = ClassSession::factory()->create();

        $this->actingAs($this->admin)->get(route('schedules.index'))->assertViewHas('schedules', fn ($s) => $s->count() === 1);

        $this->actingAs($this->admin)->post(route('academic-term.select'), ['term' => '2027-1'])->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('schedules.index'))
            ->assertViewHas('schedules', fn ($schedules) => $schedules->isEmpty())
            ->assertSee('No class sessions in First Semester, S.Y. 2027–2028 yet')
            ->assertSee($class->room->room_name)
            ->assertSee($class->section->section_name)
            ->assertSee($class->subject->subject_code);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertViewHas('recentSessions', fn ($s) => $s->isEmpty());
        $this->actingAs($this->admin)->get(route('classrooms.index'))->assertSee($class->room->room_name);

        $this->actingAs($this->admin)->post(route('academic-term.select'), ['term' => '2026-1']);
        $this->actingAs($this->admin)->get(route('schedules.index'))->assertViewHas('schedules', fn ($s) => $s->count() === 1);
    }

    public function test_new_classes_go_to_the_selected_term_and_only_conflict_within_it(): void
    {
        $existing = ClassSession::factory()->create();
        $payload = [
            'room_id' => $existing->room_id,
            'faculty_id' => $existing->faculty_id,
            'subject_id' => $existing->subject_id,
            'section_id' => $existing->section_id,
            'days' => ['M'],
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'active',
        ];

        $this->actingAs($this->admin)->post(route('schedules.store'), $payload)->assertSessionHasErrors('room_id');

        $this->actingAs($this->admin)->post(route('academic-term.select'), ['term' => '2026-2']);
        $this->actingAs($this->admin)->post(route('schedules.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $this->next->classSessions()->count());

        $this->actingAs($this->admin)->post(route('schedules.store'), $payload)->assertSessionHasErrors('room_id');
    }

    public function test_a_form_saves_to_the_term_it_was_opened_for(): void
    {
        $existing = ClassSession::factory()->create();

        $this->actingAs($this->admin)->post(route('schedules.store'), [
            'academic_term_id' => $this->next->id,
            'room_id' => $existing->room_id,
            'faculty_id' => $existing->faculty_id,
            'subject_id' => $existing->subject_id,
            'section_id' => $existing->section_id,
            'days' => ['M'],
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $this->next->classSessions()->count());
    }

    public function test_the_live_check_and_editing_stay_in_the_classs_own_term(): void
    {
        $existing = ClassSession::factory()->create();
        $copy = ClassSession::factory()->create([
            'academic_term_id' => $this->next->id,
            'room_id' => $existing->room_id,
            'faculty_id' => $existing->faculty_id,
            'section_id' => $existing->section_id,
        ]);
        $form = ['days' => ['M'], 'start_time' => '08:00', 'end_time' => '10:00', 'room_id' => $existing->room_id];

        $this->actingAs($this->admin)->postJson(route('schedules.check'), [...$form, '_record' => $copy->id])->assertJsonPath('conflicts', []);
        $this->actingAs($this->admin)->postJson(route('schedules.check'), $form)->assertJsonCount(1, 'conflicts');
        $this->actingAs($this->admin)->postJson(route('schedules.check'), [...$form, 'academic_term_id' => $this->next->id])->assertJsonCount(1, 'conflicts');

        $this->actingAs($this->admin)->put(route('schedules.update', $copy), [
            'room_id' => $copy->room_id, 'faculty_id' => $copy->faculty_id, 'subject_id' => $copy->subject_id, 'section_id' => $copy->section_id,
            'days' => ['M'], 'start_time' => '08:00', 'end_time' => '10:00', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->next->id, $copy->fresh()->academic_term_id);
    }

    public function test_planning_a_future_term_does_not_change_what_runs_today(): void
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();
        $room = Room::factory()->create();
        $planned = ClassSession::factory()->create(['academic_term_id' => $this->next->id, 'faculty_id' => $faculty->id, 'room_id' => $room->id, 'day' => 'M']);

        $this->assertNull($room->scheduledClassSessionAt(now()));
        $this->actingAs($user)->get(route('faculty.dashboard'))->assertViewHas('todaySchedules', fn ($schedules) => $schedules->isEmpty());
        $this->actingAs($user)->post(route('faculty.not-held.store', $planned), ['reason' => 'Not running yet'])->assertSessionHas('error');

        $this->actingAs($this->admin)->post(route('academic-terms.current', $this->next))->assertSessionHas('success');

        $this->assertTrue($room->scheduledClassSessionAt(now())->is($planned));
        $this->assertSame(1, AcademicTerm::where('is_current', true)->count());
    }

    public function test_faculty_see_and_upload_their_classes_by_term(): void
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();
        $old = ClassSession::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($user)->post(route('academic-term.select'), ['term' => '2026-2']);
        $this->actingAs($user)->get(route('faculty.schedule.index'))->assertSee('No classes in Second Semester, S.Y. 2026–2027 yet');

        $this->actingAs($user)->post(route('faculty.schedule.import.store'), [
            'mode' => 'replace',
            'academic_term_id' => $this->next->id,
            'rows' => [['import' => '1', 'subject_code' => 'IT-109', 'title' => 'Platform Tech', 'section' => 'AI21', 'day' => 'M', 'start_time' => '08:00', 'end_time' => '10:00', 'room_id' => $old->room_id, 'room' => '']],
        ])->assertRedirect(route('faculty.schedule.index'));

        $this->assertSame(1, $this->next->classSessions()->count());
        $this->assertSame('active', $old->fresh()->status, 'Replacing one semester leaves the others alone.');
    }

    public function test_only_administrators_change_the_current_term(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->post(route('academic-terms.current', $this->next))
            ->assertForbidden();

        $this->assertTrue($this->current->fresh()->is_current);
    }

    public function test_bad_terms_are_refused(): void
    {
        $this->actingAs($this->admin)->post(route('academic-term.select'), ['term' => '2026-4'])->assertSessionHasErrors('term');
        $this->actingAs($this->admin)->post(route('academic-term.select'), ['term' => '1999-1'])->assertStatus(422);
        $this->post(route('logout'));
        $this->post(route('academic-term.select'), ['term' => '2026-2'])->assertRedirect(route('login'));
    }
}
