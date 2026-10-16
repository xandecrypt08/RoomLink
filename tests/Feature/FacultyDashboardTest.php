<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\RoomSession;
use App\Models\TemporaryClassroomRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FacultyDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Monday 2026-10-12, 09:00
        $this->travelTo(Carbon::parse('2026-10-12 09:00:00'));
    }

    public function test_linked_faculty_sees_current_and_next_class(): void
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();

        $earlier = ClassSession::factory()->for($faculty)->create([
            'day' => 'M', 'start_time' => '06:00', 'end_time' => '07:30',
        ]);
        $current = ClassSession::factory()->for($faculty)->create([
            'day' => 'M', 'start_time' => '08:00', 'end_time' => '10:00',
        ]);
        $next = ClassSession::factory()->for($faculty)->create([
            'day' => 'M', 'start_time' => '13:00', 'end_time' => '15:00',
        ]);

        $response = $this->actingAs($user)->get(route('faculty.dashboard'));

        $response->assertOk();
        $response->assertViewHas('currentClass', fn (?ClassSession $class) => $class?->is($current));
        $response->assertViewHas('nextClass', fn (?ClassSession $class) => $class?->is($next));
        $response->assertViewHas('todaySchedules', fn ($schedules) => $schedules->pluck('id')->all() === [
            $earlier->id, $current->id, $next->id,
        ]);
    }

    public function test_stat_cards_count_held_classes_and_pending_room_requests(): void
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();

        $held = ClassSession::factory()->for($faculty)->create(['day' => 'M', 'start_time' => '06:00', 'end_time' => '07:00']);
        $notHeld = ClassSession::factory()->for($faculty)->create(['day' => 'M', 'start_time' => '07:00', 'end_time' => '08:00']);
        ClassSession::factory()->for($faculty)->create(['day' => 'M', 'start_time' => '13:00', 'end_time' => '14:00']);

        RoomSession::factory()->create(['faculty_id' => $faculty->id, 'class_session_id' => $held->id, 'room_id' => $held->room_id, 'status' => RoomSession::STATUS_ENDED, 'started_at' => now()->setTime(6, 0), 'ended_at' => now()->setTime(7, 0)]);
        RoomSession::factory()->create(['faculty_id' => $faculty->id, 'class_session_id' => $notHeld->id, 'room_id' => $notHeld->room_id, 'status' => RoomSession::STATUS_NOT_HELD, 'started_at' => now()->setTime(7, 0), 'ended_at' => now()->setTime(7, 5)]);

        TemporaryClassroomRequest::factory()->count(2)->create(['scheduled_faculty_id' => $faculty->id]);
        TemporaryClassroomRequest::factory()->create(['scheduled_faculty_id' => $faculty->id, 'status' => 'rejected']);

        $this->actingAs($user)
            ->get(route('faculty.dashboard'))
            ->assertOk()
            ->assertViewHas('heldTodayCount', 1)
            ->assertViewHas('pendingRequestCount', 2)
            ->assertSeeInOrder(['Classes Today', '3', 'Held Today', '1', 'Current Room', 'Room Requests', '2']);
    }

    public function test_dashboard_excludes_other_days_inactive_and_other_faculty_classes(): void
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();

        $todays = ClassSession::factory()->for($faculty)->create(['day' => 'M']);
        ClassSession::factory()->for($faculty)->create(['day' => 'T']);
        ClassSession::factory()->for($faculty)->create(['day' => 'M', 'status' => 'inactive']);
        ClassSession::factory()->create(['day' => 'M']);

        $response = $this->actingAs($user)->get(route('faculty.dashboard'));

        $response->assertViewHas('todaySchedules', fn ($schedules) => $schedules->pluck('id')->all() === [$todays->id]);
    }

    public function test_faculty_account_without_a_faculty_record_sees_no_classes(): void
    {
        $user = User::factory()->faculty()->create();
        ClassSession::factory()->create(['day' => 'M']);

        $response = $this->actingAs($user)->get(route('faculty.dashboard'));

        $response->assertOk();
        $response->assertViewHas('facultyRecord', null);
        $response->assertViewHas('todaySchedules', fn ($schedules) => $schedules->isEmpty());
    }

    public function test_new_faculty_record_is_linked_to_the_faculty_account_with_the_same_employee_id(): void
    {
        $user = User::factory()->faculty()->create(['employee_student_id' => 'FAC001']);

        $faculty = Faculty::factory()->create(['employee_id' => 'FAC001']);

        $this->assertTrue($faculty->user->is($user));
        $this->assertTrue($user->faculty->is($faculty));
    }

    public function test_faculty_record_is_not_linked_to_a_non_faculty_account(): void
    {
        User::factory()->student()->create(['employee_student_id' => '2024-0001']);

        $faculty = Faculty::factory()->create(['employee_id' => '2024-0001']);

        $this->assertNull($faculty->user_id);
    }
}
