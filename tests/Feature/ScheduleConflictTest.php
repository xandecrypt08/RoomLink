<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /**
     * Monday 08:00–10:00 in Room 101, taught by Juan Dela Cruz to BSIT-1A.
     */
    private ClassSession $existing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();

        $this->existing = ClassSession::factory()->create([
            'room_id' => Room::factory()->create(['room_name' => 'Room 101']),
            'faculty_id' => Faculty::factory()->create(['first_name' => 'Juan', 'last_name' => 'Dela Cruz']),
            'subject_id' => Subject::factory()->create(['subject_code' => 'IT 101']),
            'section_id' => Section::factory()->create(['section_name' => 'BSIT-1A']),
            'day' => 'M',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);
    }

    /**
     * A schedule that shares nothing with the existing one unless overridden.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'room_id' => Room::factory()->create()->id,
            'faculty_id' => Faculty::factory()->create()->id,
            'subject_id' => Subject::factory()->create()->id,
            'section_id' => Section::factory()->create()->id,
            'day' => 'M',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'active',
        ], $overrides);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sharedResources(): array
    {
        return [
            'same room' => ['room_id'],
            'same faculty' => ['faculty_id'],
            'same section' => ['section_id'],
        ];
    }

    #[DataProvider('sharedResources')]
    public function test_overlapping_schedule_sharing_a_resource_is_rejected(string $field): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('schedules.create'))
            ->post(route('schedules.store'), $this->payload([$field => $this->existing->{$field}]));

        $response->assertRedirect(route('schedules.create'));
        $response->assertSessionHasErrors([$field]);
        $this->assertDatabaseCount('class_sessions', 1);
    }

    public function test_conflict_message_names_the_clashing_class(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('schedules.store'), $this->payload(['faculty_id' => $this->existing->faculty_id]));

        $response->assertSessionHasErrors([
            'faculty_id' => 'This faculty member is already teaching at that time: IT 101 (BSIT-1A) with Juan Dela Cruz in Room 101, 08:00–10:00.',
        ]);
    }

    public function test_every_conflict_is_reported_at_once(): void
    {
        $response = $this->actingAs($this->admin)->post(route('schedules.store'), $this->payload([
            'room_id' => $this->existing->room_id,
            'faculty_id' => $this->existing->faculty_id,
            'section_id' => $this->existing->section_id,
        ]));

        $response->assertSessionHasErrors(['room_id', 'faculty_id', 'section_id']);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function nonConflictingSlots(): array
    {
        return [
            'starts when the other ends' => ['M', '10:00', '12:00'],
            'ends when the other starts' => ['M', '06:00', '08:00'],
            'same time, different day' => ['T', '08:00', '10:00'],
        ];
    }

    #[DataProvider('nonConflictingSlots')]
    public function test_schedule_that_does_not_overlap_is_saved(string $day, string $start, string $end): void
    {
        $response = $this->actingAs($this->admin)->post(route('schedules.store'), $this->payload([
            'room_id' => $this->existing->room_id,
            'faculty_id' => $this->existing->faculty_id,
            'section_id' => $this->existing->section_id,
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end,
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules.index'));
        $this->assertDatabaseCount('class_sessions', 2);
    }

    public function test_schedule_inside_an_existing_one_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('schedules.store'), $this->payload([
            'room_id' => $this->existing->room_id,
            'start_time' => '08:30',
            'end_time' => '09:30',
        ]));

        $response->assertSessionHasErrors(['room_id']);
    }

    public function test_inactive_schedules_do_not_conflict(): void
    {
        $this->existing->update(['status' => 'inactive']);

        $response = $this->actingAs($this->admin)->post(route('schedules.store'), $this->payload([
            'room_id' => $this->existing->room_id,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('class_sessions', 2);
    }

    public function test_new_inactive_schedule_is_saved_even_if_it_overlaps(): void
    {
        $response = $this->actingAs($this->admin)->post(route('schedules.store'), $this->payload([
            'room_id' => $this->existing->room_id,
            'status' => 'inactive',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('class_sessions', 2);
    }

    public function test_editing_a_schedule_does_not_conflict_with_itself(): void
    {
        $response = $this->actingAs($this->admin)->put(route('schedules.update', $this->existing), [
            'room_id' => $this->existing->room_id,
            'faculty_id' => $this->existing->faculty_id,
            'subject_id' => $this->existing->subject_id,
            'section_id' => $this->existing->section_id,
            'day' => 'M',
            'start_time' => '08:00',
            'end_time' => '10:30',
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('10:30', $this->existing->fresh()->end_time->format('H:i'));
    }

    public function test_editing_a_schedule_into_a_conflict_is_rejected(): void
    {
        $other = ClassSession::factory()->create([
            'day' => 'M',
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
        ]);

        $response = $this->actingAs($this->admin)->put(route('schedules.update', $other), [
            'room_id' => $other->room_id,
            'faculty_id' => $this->existing->faculty_id,
            'subject_id' => $other->subject_id,
            'section_id' => $other->section_id,
            'day' => 'M',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['faculty_id']);
        $this->assertSame('13:00', $other->fresh()->start_time->format('H:i'));
    }
}
