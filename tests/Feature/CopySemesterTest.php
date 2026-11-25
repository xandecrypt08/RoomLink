<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CopySemesterTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private AcademicTerm $from;

    private AcademicTerm $to;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->from = AcademicTerm::current();
        $this->to = AcademicTerm::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function copy(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('schedules.copy'), [
            'academic_term_id' => $this->to->id,
            'from_term_id' => $this->from->id,
            ...$overrides,
        ]);
    }

    public function test_active_classes_are_copied_and_the_originals_stay(): void
    {
        $class = ClassSession::factory()->create(['day' => 'W', 'start_time' => '13:00:00', 'end_time' => '14:30:00', 'description' => 'Bring laptops']);
        ClassSession::factory()->create(['status' => 'inactive']);

        $this->copy()->assertRedirect(route('schedules.index'))->assertSessionHas('success', "1 class copied to {$this->to->label}.");

        $copy = $this->to->classSessions()->sole();
        $fields = ['room_id', 'faculty_id', 'subject_id', 'section_id', 'day', 'description'];
        $this->assertSame($class->only($fields), $copy->only($fields));
        $this->assertSame('13:00 14:30 active', $copy->start_time->format('H:i').' '.$copy->end_time->format('H:i').' '.$copy->status);
        $this->assertSame(2, $this->from->classSessions()->count());
    }

    public function test_copying_twice_adds_nothing_and_says_what_was_skipped(): void
    {
        $class = ClassSession::factory()->create();

        $this->copy();
        $this->copy()->assertSessionHas('skipped', fn ($skipped) => count($skipped) === 1 && str_contains($skipped[0], $class->subject->subject_code));

        $this->assertSame(1, $this->to->classSessions()->count());
        $this->actingAs($this->admin)->get(route('schedules.index'))->assertSee('1 class not copied');
    }

    public function test_a_class_whose_room_is_taken_is_skipped_and_the_rest_are_copied(): void
    {
        $class = ClassSession::factory()->create();
        ClassSession::factory()->create(['day' => 'F']);
        ClassSession::factory()->create(['academic_term_id' => $this->to->id, 'room_id' => $class->room_id]);

        $this->copy()->assertSessionHas('success', "1 class copied to {$this->to->label}.");

        $this->assertSame(2, $this->to->classSessions()->count());
    }

    public function test_one_faculty_members_classes_can_be_copied(): void
    {
        $class = ClassSession::factory()->create();
        ClassSession::factory()->create(['day' => 'F']);

        $this->copy(['faculty_id' => $class->faculty_id]);

        $this->assertSame([$class->faculty_id], $this->to->classSessions()->pluck('faculty_id')->all());
    }

    public function test_a_semester_cannot_be_copied_into_itself(): void
    {
        ClassSession::factory()->create();

        $this->copy(['academic_term_id' => $this->from->id])->assertSessionHasErrors('from_term_id');
    }

    public function test_the_page_offers_other_semesters_that_have_classes(): void
    {
        ClassSession::factory()->create();
        $empty = AcademicTerm::factory()->create();

        $this->actingAs($this->admin)->get(route('schedules.index'))->assertDontSee('data-dialog="copy"', false);

        $this->actingAs($this->admin)->withSession(['academic_term_id' => $this->to->id])
            ->get(route('schedules.index'))
            ->assertSee('data-dialog="copy"', false)
            ->assertViewHas('copySources', fn ($terms) => $terms->pluck('id')->all() === [$this->from->id] && ! $terms->contains($empty));
    }

    public function test_only_administrators_can_copy(): void
    {
        $this->actingAs(User::factory()->faculty()->create())->post(route('schedules.copy'))->assertForbidden();
    }
}
