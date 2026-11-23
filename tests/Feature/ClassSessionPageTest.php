<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClassSessionPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_one_page_shows_sessions_sections_and_the_import(): void
    {
        $schedule = ClassSession::factory()->create();
        $empty = Section::factory()->create(['section_name' => 'EMPTY-1']);

        $this->actingAs($this->admin)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee($schedule->subject->subject_code)
            ->assertSee($schedule->section->section_name)
            ->assertSee($empty->section_name)
            ->assertSee('data-dialog="load"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_the_page_opens_without_any_records(): void
    {
        $this->actingAs($this->admin)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('There are no rooms yet');
    }

    public function test_one_form_saves_a_class_on_several_days_and_creates_what_is_new(): void
    {
        $this->actingAs($this->admin)
            ->post(route('schedules.store'), $this->payload([
                'faculty_id' => 'new',
                'faculty_employee_id' => '250702',
                'faculty_first_name' => 'Janine',
                'faculty_last_name' => 'Memoracion',
            ]))
            ->assertRedirect(route('schedules.index'))
            ->assertSessionHas('success', '2 class sessions created successfully.');

        $this->assertEqualsCanonicalizing(['M', 'Th'], ClassSession::pluck('day')->all());
        $this->assertSame('IT-109', Subject::sole()->subject_code);
        $this->assertSame('AI21', Section::sole()->section_name);
        $this->assertSame('250702', Faculty::sole()->employee_id);
    }

    public function test_existing_subjects_and_sections_are_reused_however_they_are_typed(): void
    {
        $subject = Subject::factory()->create(['subject_code' => 'IT-109']);
        $section = Section::factory()->create(['section_name' => 'AI21']);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), $this->payload(['subject_code' => 'it 109', 'subject_name' => '', 'section_name' => ' ai21 ']))
            ->assertSessionHasNoErrors();

        $this->assertSame([$subject->id], ClassSession::pluck('subject_id')->unique()->all());
        $this->assertSame([$section->id], ClassSession::pluck('section_id')->unique()->all());
        $this->assertSame(1, Subject::count());
        $this->assertSame(1, Section::count());
    }

    public function test_a_new_subject_needs_its_title(): void
    {
        $this->actingAs($this->admin)
            ->post(route('schedules.store'), $this->payload(['subject_name' => '']))
            ->assertSessionHasErrors(['subject_name' => 'IT-109 is a new subject. Enter its descriptive title.']);

        $this->assertSame(0, Subject::count());
    }

    public function test_a_conflict_on_one_day_saves_nothing_and_names_the_day(): void
    {
        $existing = ClassSession::factory()->create(['day' => 'Th', 'start_time' => '08:00:00', 'end_time' => '10:00:00']);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), $this->payload([
                'room_id' => $existing->room_id,
                'faculty_id' => 'new',
                'faculty_employee_id' => '250702',
                'faculty_first_name' => 'Janine',
                'faculty_last_name' => 'Memoracion',
            ]))
            ->assertSessionHasErrors('room_id');

        $this->assertStringStartsWith('Thursday: This room is already booked', session('errors')->first('room_id'));
        $this->assertSame(1, ClassSession::count());
        $this->assertDatabaseMissing('faculties', ['employee_id' => '250702']);
        $this->assertDatabaseMissing('sections', ['section_name' => 'AI21']);
    }

    public function test_a_day_is_required(): void
    {
        $payload = $this->payload();
        unset($payload['days']);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), $payload)
            ->assertSessionHasErrors(['days' => 'Choose at least one day.']);
    }

    public function test_save_and_add_another_keeps_the_details_that_stay_the_same(): void
    {
        $payload = $this->payload(['another' => '1']);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('schedules.store'), $payload)
            ->assertOk()
            ->assertSee('data-repeat', false);
    }

    public function test_editing_keeps_one_day(): void
    {
        $schedule = ClassSession::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('schedules.update', $schedule), $this->payload())
            ->assertSessionHasErrors('days');

        $this->actingAs($this->admin)
            ->put(route('schedules.update', $schedule), $this->payload(['days' => ['F']]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['F', '08:00'], [$schedule->fresh()->day, $schedule->fresh()->start_time->format('H:i')]);
        $this->assertSame(1, ClassSession::count());
    }

    public function test_links_to_the_old_forms_open_the_pop_ups(): void
    {
        $schedule = ClassSession::factory()->create();

        foreach ([route('schedules.create'), route('schedules.edit', $schedule), route('schedules.import.create')] as $url) {
            $this->actingAs($this->admin)
                ->followingRedirects()
                ->get($url)
                ->assertOk()
                ->assertSee('data-open-now', false);
        }

        $this->actingAs($this->admin)->get('/admin/sections')->assertRedirect(route('schedules.index'));
    }

    public function test_sections_are_managed_here_and_kept_while_they_have_classes(): void
    {
        $used = ClassSession::factory()->create()->section;

        $this->actingAs($this->admin)
            ->post(route('sections.store'), ['section_name' => 'AI99'])
            ->assertRedirect(route('schedules.index'));

        $this->actingAs($this->admin)
            ->delete(route('sections.destroy', $used))
            ->assertRedirect(route('schedules.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('sections', ['section_name' => 'AI99']);
        $this->assertModelExists($used);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'room_id' => Room::factory()->create()->id,
            'faculty_id' => isset($overrides['faculty_id']) ? null : Faculty::factory()->create()->id,
            'subject_code' => 'IT 109',
            'subject_name' => 'IT Elective I',
            'section_name' => 'AI21',
            'days' => ['M', 'Th'],
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'active',
            ...$overrides,
        ];
    }
}
