<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\TemporaryClassroomRequest;
use App\Models\User;
use App\Notifications\TemporaryClassroomRequested;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TemporaryClassroomRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday, 9:00 AM
        $this->travelTo('2026-10-12 09:00:00');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('faculty.temporary-requests.index'))
            ->assertRedirect(route('login'));
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('faculty.temporary-requests.create'))
            ->assertForbidden();
    }

    public function test_scanning_a_room_shows_the_faculty_scheduled_in_it(): void
    {
        [$requesterUser] = $this->createFacultyMember();
        [, $scheduledFaculty] = $this->createFacultyMember();

        $room = Room::factory()->create(['room_name' => 'Room 301']);

        ClassSession::factory()->create([
            'room_id' => $room->id,
            'faculty_id' => $scheduledFaculty->id,
            'day' => 'M',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $this->actingAs($requesterUser)
            ->get(route('faculty.temporary-requests.create', ['code' => $room->qr_code]))
            ->assertOk()
            ->assertSee('Room 301')
            ->assertSee($scheduledFaculty->full_name)
            ->assertSee('Submit Request');
    }

    public function test_scanning_an_unknown_code_shows_an_error(): void
    {
        [$requesterUser] = $this->createFacultyMember();

        $this->actingAs($requesterUser)
            ->get(route('faculty.temporary-requests.create', ['code' => 'ROOM-UNKNOWN']))
            ->assertOk()
            ->assertSee('No classroom matches the code')
            ->assertDontSee('Submit Request');
    }

    public function test_submitting_creates_a_pending_request_and_notifies_the_scheduled_faculty(): void
    {
        Notification::fake();

        [$requesterUser, $requester] = $this->createFacultyMember();
        [$scheduledUser, $scheduledFaculty] = $this->createFacultyMember();

        $room = Room::factory()->create();

        $scheduledSession = ClassSession::factory()->create([
            'room_id' => $room->id,
            'faculty_id' => $scheduledFaculty->id,
            'day' => 'M',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $requesterSession = ClassSession::factory()->create([
            'faculty_id' => $requester->id,
            'day' => 'M',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
                'requester_class_session_id' => $requesterSession->id,
                'reason' => 'Projector in my assigned room is broken.',
            ])
            ->assertRedirect(route('faculty.temporary-requests.index'))
            ->assertSessionHas('success', 'Request submitted. The scheduled faculty has been notified.');

        $this->assertDatabaseHas('temporary_classroom_requests', [
            'requester_id' => $requester->id,
            'requester_class_session_id' => $requesterSession->id,
            'room_id' => $room->id,
            'scheduled_class_session_id' => $scheduledSession->id,
            'scheduled_faculty_id' => $scheduledFaculty->id,
            'reason' => 'Projector in my assigned room is broken.',
            'status' => 'pending',
        ]);

        Notification::assertSentTo(
            $scheduledUser,
            TemporaryClassroomRequested::class,
            fn (TemporaryClassroomRequested $notification) => $notification->temporaryClassroomRequest->room_id === $room->id
        );
    }

    public function test_submitting_for_a_room_with_no_class_now_does_not_notify_anyone(): void
    {
        Notification::fake();

        [$requesterUser, $requester] = $this->createFacultyMember();
        [, $scheduledFaculty] = $this->createFacultyMember();

        $room = Room::factory()->create();

        // Scheduled later in the day, so the room is free at 9:00 AM
        ClassSession::factory()->create([
            'room_id' => $room->id,
            'faculty_id' => $scheduledFaculty->id,
            'day' => 'M',
            'start_time' => '13:00',
            'end_time' => '15:00',
        ]);

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
                'reason' => 'Aircon is not working.',
            ])
            ->assertRedirect(route('faculty.temporary-requests.index'))
            ->assertSessionHas('success', 'Request submitted. No class is scheduled in this room right now.');

        $this->assertDatabaseHas('temporary_classroom_requests', [
            'requester_id' => $requester->id,
            'room_id' => $room->id,
            'scheduled_class_session_id' => null,
            'scheduled_faculty_id' => null,
            'status' => 'pending',
        ]);

        Notification::assertNothingSent();
    }

    public function test_submitting_without_a_reason_is_rejected(): void
    {
        [$requesterUser] = $this->createFacultyMember();

        $room = Room::factory()->create();

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
            ])
            ->assertSessionHasErrors(['reason' => 'The reason field is required.']);

        $this->assertDatabaseCount('temporary_classroom_requests', 0);
    }

    public function test_linking_another_faculty_members_class_is_rejected(): void
    {
        [$requesterUser] = $this->createFacultyMember();

        $room = Room::factory()->create();
        $otherFacultysSession = ClassSession::factory()->create();

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
                'requester_class_session_id' => $otherFacultysSession->id,
                'reason' => 'Room is flooded.',
            ])
            ->assertSessionHasErrors(['requester_class_session_id' => 'The selected requester class session id is invalid.']);

        $this->assertDatabaseCount('temporary_classroom_requests', 0);
    }

    public function test_requesting_a_room_under_maintenance_is_rejected(): void
    {
        [$requesterUser] = $this->createFacultyMember();

        $room = Room::factory()->create(['status' => 'maintenance']);

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
                'reason' => 'Room is flooded.',
            ])
            ->assertSessionHasErrors(['room_id' => 'This room is under maintenance and cannot be requested.']);

        $this->assertDatabaseCount('temporary_classroom_requests', 0);
    }

    public function test_requesting_a_room_you_are_already_scheduled_in_is_rejected(): void
    {
        [$requesterUser, $requester] = $this->createFacultyMember();

        $room = Room::factory()->create();

        ClassSession::factory()->create([
            'room_id' => $room->id,
            'faculty_id' => $requester->id,
            'day' => 'M',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
                'reason' => 'Room is flooded.',
            ])
            ->assertSessionHasErrors(['room_id' => 'You are already scheduled in this room right now.']);

        $this->assertDatabaseCount('temporary_classroom_requests', 0);
    }

    public function test_a_second_pending_request_for_the_same_room_is_rejected(): void
    {
        [$requesterUser, $requester] = $this->createFacultyMember();

        $room = Room::factory()->create();

        TemporaryClassroomRequest::factory()->create([
            'requester_id' => $requester->id,
            'room_id' => $room->id,
        ]);

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
                'reason' => 'Room is flooded.',
            ])
            ->assertSessionHasErrors(['room_id' => 'You already have a pending request for this room.']);

        $this->assertDatabaseCount('temporary_classroom_requests', 1);
    }

    public function test_scheduled_faculty_sees_new_incoming_requests_and_they_are_marked_read(): void
    {
        [$requesterUser, $requester] = $this->createFacultyMember();
        [$scheduledUser, $scheduledFaculty] = $this->createFacultyMember();

        $room = Room::factory()->create(['room_name' => 'Room 205']);

        ClassSession::factory()->create([
            'room_id' => $room->id,
            'faculty_id' => $scheduledFaculty->id,
            'day' => 'M',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $this->actingAs($requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $room->id,
                'reason' => 'Ceiling leak in my room.',
            ]);

        $this->assertCount(1, $scheduledUser->unreadNotifications);

        $this->actingAs($scheduledUser)
            ->get(route('faculty.temporary-requests.index'))
            ->assertOk()
            ->assertSee($requester->full_name)
            ->assertSee('Room 205')
            ->assertSee('Ceiling leak in my room.')
            ->assertSee('New');

        $this->assertCount(0, $scheduledUser->fresh()->unreadNotifications);
    }

    public function test_requester_sees_their_request_status(): void
    {
        [$requesterUser, $requester] = $this->createFacultyMember();

        TemporaryClassroomRequest::factory()->create([
            'requester_id' => $requester->id,
            'room_id' => Room::factory()->create(['room_name' => 'Room 110'])->id,
            'reason' => 'Broken chairs.',
        ]);

        $this->actingAs($requesterUser)
            ->get(route('faculty.temporary-requests.index'))
            ->assertOk()
            ->assertSee('Room 110')
            ->assertSee('Broken chairs.')
            ->assertSee('Pending');
    }

    /**
     * Create a faculty login account and its linked Faculty record.
     *
     * @return array{0: User, 1: Faculty}
     */
    private function createFacultyMember(): array
    {
        $faculty = Faculty::factory()->create();

        $user = User::factory()->faculty()->create([
            'employee_student_id' => $faculty->employee_id,
        ]);

        return [$user, $faculty];
    }
}
