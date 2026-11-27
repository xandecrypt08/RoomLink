<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function types(): array
    {
        return ['room' => ['room'], 'section' => ['section'], 'faculty' => ['faculty']];
    }

    #[DataProvider('types')]
    public function test_it_shows_only_the_week_of_what_was_chosen(string $type): void
    {
        $class = ClassSession::factory()->create(['day' => 'W', 'start_time' => '09:10:00', 'end_time' => '10:40:00']);
        $other = ClassSession::factory()->create();
        ClassSession::factory()->create(["{$type}_id" => $class->{"{$type}_id"}, 'status' => 'inactive']);
        ClassSession::factory()->create(["{$type}_id" => $class->{"{$type}_id"}, 'academic_term_id' => AcademicTerm::factory()->create()->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('timetable', ['type' => $type, 'id' => $class->{"{$type}_id"}]))
            ->assertOk()
            ->assertViewHas('classes', fn ($classes) => $classes->pluck('id')->all() === [$class->id])
            ->assertSee('9:10–10:40 AM')
            ->assertDontSee($other->subject->subject_code);
    }

    public function test_classes_outside_the_usual_hours_and_on_sunday_fit(): void
    {
        $class = ClassSession::factory()->create(['day' => 'Su', 'start_time' => '05:30:00', 'end_time' => '23:59:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('timetable', ['type' => 'room', 'id' => $class->room_id]))
            ->assertOk()
            ->assertSee('Sunday')
            ->assertSee('5 AM')
            ->assertSee('11 PM');
    }

    public function test_students_see_rooms_and_sections_but_not_faculty(): void
    {
        $class = ClassSession::factory()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('timetable', ['type' => 'section', 'id' => $class->section_id]))->assertOk()->assertSee($class->subject->subject_code);

        $this->actingAs($student)
            ->get(route('timetable', ['type' => 'faculty', 'id' => $class->faculty_id]))
            ->assertOk()
            ->assertViewHas('type', 'room');
    }

    public function test_unknown_choices_and_guests(): void
    {
        $this->get(route('timetable'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('timetable', ['type' => 'planet', 'id' => 999]))
            ->assertOk()
            ->assertSee('Choose a room');
    }
}
