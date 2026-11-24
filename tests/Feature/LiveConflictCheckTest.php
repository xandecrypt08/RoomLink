<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LiveConflictCheckTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private ClassSession $existing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->existing = ClassSession::factory()->create(['day' => 'M', 'start_time' => '08:00:00', 'end_time' => '10:00:00']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function sharedResources(): array
    {
        return [
            'room' => ['room_id', 'This room is already booked'],
            'faculty' => ['faculty_id', 'This faculty member is already teaching'],
            'section' => ['section_id', 'This section already has a class'],
        ];
    }

    #[DataProvider('sharedResources')]
    public function test_one_choice_is_enough_to_warn_before_the_rest_is_filled_in(string $field, string $message): void
    {
        $choice = $field === 'section_id'
            ? ['section_name' => mb_strtolower($this->existing->section->section_name)]
            : [$field => $this->existing->{$field}];

        $response = $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), ['days' => ['M'], 'start_time' => '09:00', 'end_time' => '11:00', ...$choice])
            ->assertOk()
            ->assertJsonCount(1, 'conflicts')
            ->assertJsonPath('complete', false);

        $this->assertStringStartsWith($message, $response->json("conflicts.{$field}.0"));
        $this->assertSame(1, ClassSession::count());
    }

    public function test_nothing_is_reported_when_the_time_or_day_is_free(): void
    {
        foreach ([['M', '10:00', '12:00'], ['T', '08:00', '10:00']] as [$day, $start, $end]) {
            $this->actingAs($this->admin)
                ->postJson(route('schedules.check'), $this->fullForm(['days' => [$day], 'start_time' => $start, 'end_time' => $end]))
                ->assertExactJson(['conflicts' => [], 'complete' => true]);
        }
    }

    public function test_an_unfinished_form_is_not_an_error(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), ['room_id' => '', 'faculty_id' => '', 'section_name' => '', 'start_time' => '', 'end_time' => ''])
            ->assertExactJson(['conflicts' => [], 'complete' => false]);
    }

    public function test_an_end_before_the_start_is_pointed_out(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), ['start_time' => '10:00', 'end_time' => '09:00'])
            ->assertJsonPath('conflicts.end_time.0', 'The class must end after it starts.');
    }

    public function test_the_class_being_edited_does_not_conflict_with_itself(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), $this->fullForm(['_record' => $this->existing->id]))
            ->assertExactJson(['conflicts' => [], 'complete' => true]);
    }

    public function test_inactive_classes_and_new_faculty_do_not_conflict(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), $this->fullForm(['status' => 'inactive']))
            ->assertJsonPath('conflicts', []);

        $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), ['days' => ['M'], 'start_time' => '08:00', 'end_time' => '10:00', 'faculty_id' => 'new'])
            ->assertJsonPath('conflicts', []);
    }

    public function test_free_rooms_are_suggested_when_the_room_is_taken(): void
    {
        $nearby = Room::factory()->create(['floor_id' => $this->existing->room->floor_id, 'room_name' => 'Z Nearby']);
        $elsewhere = Room::factory()->create(['room_name' => 'A Elsewhere']);
        Room::factory()->create(['status' => 'maintenance']);
        ClassSession::factory()->create(['day' => 'W', 'start_time' => '09:00:00', 'end_time' => '10:00:00']);

        $response = $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), ['days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '10:00', 'room_id' => $this->existing->room_id]);

        $this->assertSame([$nearby->id, $elsewhere->id], array_column($response->json('free_rooms'), 'id'), 'Same building first; only rooms free on both days.');

        $this->actingAs($this->admin)
            ->postJson(route('schedules.check'), ['days' => ['M'], 'start_time' => '08:00', 'end_time' => '10:00', 'faculty_id' => $this->existing->faculty_id])
            ->assertJsonMissingPath('free_rooms');
    }

    public function test_only_administrators_can_check_the_class_form(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->postJson(route('schedules.check'), [])
            ->assertForbidden();
    }

    public function test_faculty_see_conflicts_while_editing_their_load(): void
    {
        $user = User::factory()->faculty()->create();
        Faculty::factory()->for($user)->create();
        $subject = Subject::factory()->create(['subject_code' => 'IT-109', 'subject_name' => 'Platform Tech']);
        $row = ['import' => '1', 'subject_code' => 'it 109', 'title' => '', 'section' => 'AI21', 'day' => 'M', 'start_time' => '09:00', 'end_time' => '11:00', 'room' => ''];

        $response = $this->actingAs($user)
            ->postJson(route('faculty.schedule.import.check'), ['mode' => 'add', 'rows' => [
                [...$row, 'room_id' => (string) $this->existing->room_id],
                [...$row, 'day' => 'T', 'room_id' => (string) Room::factory()->create()->id],
            ]])
            ->assertOk()
            ->assertJsonPath('rows.0.problem', true)
            ->assertJsonPath('rows.1.problem', false)
            ->assertJsonPath('rows.0.title', $subject->subject_name);

        $this->assertStringContainsString('This room is already booked', $response->json('rows.0.status'));
        $this->assertStringContainsString('Ready', $response->json('rows.1.status'));
        $this->assertSame(1, ClassSession::count());
    }

    /**
     * The existing class's own room, faculty, section, day and time.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function fullForm(array $overrides = []): array
    {
        return [
            'room_id' => $this->existing->room_id,
            'faculty_id' => $this->existing->faculty_id,
            'section_name' => $this->existing->section->section_name,
            'days' => ['M'],
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'active',
            ...$overrides,
        ];
    }
}
