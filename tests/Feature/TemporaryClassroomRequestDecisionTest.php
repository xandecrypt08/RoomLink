<?php

namespace Tests\Feature;

use App\Actions\ApproveTemporaryClassroomRequest;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\TemporaryClassroomRequest;
use App\Models\User;
use App\Notifications\TemporaryClassroomRequestReviewed;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TemporaryClassroomRequestDecisionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $scheduledUser;

    private Faculty $scheduledFaculty;

    private User $requesterUser;

    private Faculty $requester;

    private Room $room;

    private ClassSession $scheduledClass;

    private ClassSession $requesterClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday, 9:00 AM
        $this->travelTo('2026-10-12 09:00:00');

        [$this->scheduledUser, $this->scheduledFaculty] = $this->createFacultyMember();
        [$this->requesterUser, $this->requester] = $this->createFacultyMember();

        $this->room = Room::factory()->create(['room_name' => 'ACAD 301']);

        $this->scheduledClass = ClassSession::factory()->create([
            'room_id' => $this->room->id,
            'faculty_id' => $this->scheduledFaculty->id,
            'day' => 'M',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $this->requesterClass = ClassSession::factory()->create([
            'faculty_id' => $this->requester->id,
            'day' => 'M',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);
    }

    public function test_scheduled_faculty_sees_approve_and_reject_buttons(): void
    {
        $temporaryRequest = $this->pendingRequest();

        $this->actingAs($this->scheduledUser)
            ->get(route('faculty.temporary-requests.index'))
            ->assertOk()
            ->assertSee(route('faculty.temporary-requests.approve', $temporaryRequest))
            ->assertSee(route('faculty.temporary-requests.reject', $temporaryRequest));
    }

    public function test_scheduled_faculty_can_approve_and_the_requester_is_notified(): void
    {
        Notification::fake();

        $temporaryRequest = $this->pendingRequest();

        $this->actingAs($this->scheduledUser)
            ->post(route('faculty.temporary-requests.approve', $temporaryRequest))
            ->assertRedirect(route('faculty.temporary-requests.index'))
            ->assertSessionHas('success');

        $temporaryRequest->refresh();

        $this->assertSame('approved', $temporaryRequest->status);
        $this->assertTrue($temporaryRequest->decided_at->equalTo(now()));

        Notification::assertSentTo(
            $this->requesterUser,
            TemporaryClassroomRequestReviewed::class,
            fn (TemporaryClassroomRequestReviewed $notification) => $notification->temporaryClassroomRequest->is($temporaryRequest)
        );
    }

    public function test_approving_rejects_other_pending_requests_for_the_same_room_and_time(): void
    {
        [, $otherRequester] = $this->createFacultyMember();

        $approvedRequest = $this->pendingRequest();
        $competingRequest = $this->pendingRequest(['requester_id' => $otherRequester->id]);
        $otherRoomRequest = $this->pendingRequest([
            'requester_id' => $otherRequester->id,
            'room_id' => Room::factory()->create()->id,
        ]);

        $this->actingAs($this->scheduledUser)
            ->post(route('faculty.temporary-requests.approve', $approvedRequest));

        $this->assertSame('approved', $approvedRequest->fresh()->status);

        $this->assertSame('rejected', $competingRequest->fresh()->status);
        $this->assertSame(ApproveTemporaryClassroomRequest::COMPETING_REQUEST_NOTE, $competingRequest->fresh()->decision_note);

        $this->assertSame('pending', $otherRoomRequest->fresh()->status);
    }

    public function test_scheduled_faculty_can_reject_with_a_note(): void
    {
        $temporaryRequest = $this->pendingRequest();

        $this->actingAs($this->scheduledUser)
            ->post(route('faculty.temporary-requests.reject', $temporaryRequest), [
                'decision_note' => 'I am using the room for a quiz.',
            ])
            ->assertRedirect(route('faculty.temporary-requests.index'))
            ->assertSessionHas('success');

        $temporaryRequest->refresh();

        $this->assertSame('rejected', $temporaryRequest->status);
        $this->assertSame('I am using the room for a quiz.', $temporaryRequest->decision_note);

        $this->assertSame(
            'Your request to use ACAD 301 was rejected. Note: I am using the room for a quiz.',
            $this->requesterUser->notifications()->first()->data['message']
        );
    }

    public function test_rejection_note_cannot_be_longer_than_255_characters(): void
    {
        $temporaryRequest = $this->pendingRequest();

        $this->actingAs($this->scheduledUser)
            ->post(route('faculty.temporary-requests.reject', $temporaryRequest), [
                'decision_note' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('decision_note');

        $this->assertSame('pending', $temporaryRequest->fresh()->status);
    }

    public function test_only_the_scheduled_faculty_can_decide(): void
    {
        [$otherUser] = $this->createFacultyMember();

        $temporaryRequest = $this->pendingRequest();

        foreach ([$otherUser, $this->requesterUser] as $user) {
            $this->actingAs($user)
                ->post(route('faculty.temporary-requests.approve', $temporaryRequest))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('faculty.temporary-requests.reject', $temporaryRequest))
                ->assertForbidden();
        }

        $this->actingAs(User::factory()->create())
            ->post(route('faculty.temporary-requests.approve', $temporaryRequest))
            ->assertForbidden();

        $this->assertSame('pending', $temporaryRequest->fresh()->status);
    }

    public function test_an_already_decided_request_cannot_be_decided_again(): void
    {
        $temporaryRequest = $this->pendingRequest(['status' => 'rejected']);

        $this->actingAs($this->scheduledUser)
            ->post(route('faculty.temporary-requests.approve', $temporaryRequest))
            ->assertRedirect(route('faculty.temporary-requests.index'))
            ->assertSessionHas('error', 'This request was already rejected.');

        $this->assertSame('rejected', $temporaryRequest->fresh()->status);
    }

    public function test_a_request_expires_once_the_scheduled_class_is_over(): void
    {
        $temporaryRequest = $this->pendingRequest();

        $this->travelTo('2026-10-12 10:00:00');

        $this->actingAs($this->scheduledUser)
            ->post(route('faculty.temporary-requests.approve', $temporaryRequest))
            ->assertSessionHas('error', 'This request has expired because the class time it was for is over.');

        $this->assertSame('pending', $temporaryRequest->fresh()->status);

        $this->actingAs($this->scheduledUser)
            ->get(route('faculty.temporary-requests.index'))
            ->assertOk()
            ->assertSee('Expired')
            ->assertDontSee(route('faculty.temporary-requests.approve', $temporaryRequest));
    }

    public function test_approved_requester_can_start_a_session_linked_to_their_class(): void
    {
        $this->pendingRequest(['status' => 'approved']);

        $this->actingAs($this->requesterUser)
            ->get(route('faculty.sessions.scan', $this->room->qr_code))
            ->assertOk()
            ->assertSee('Your temporary classroom request for this room was approved')
            ->assertSee('Start Session');

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code])
            ->assertRedirect(route('faculty.dashboard'));

        $this->assertDatabaseHas('room_sessions', [
            'room_id' => $this->room->id,
            'faculty_id' => $this->requester->id,
            'class_session_id' => $this->requesterClass->id,
            'status' => RoomSession::STATUS_ACTIVE,
        ]);

        $this->assertSame('occupied', $this->room->fresh()->status);
    }

    public function test_requester_is_told_to_scan_the_room_once_approved(): void
    {
        $this->pendingRequest(['status' => 'approved']);

        $this->actingAs($this->requesterUser)
            ->get(route('faculty.temporary-requests.index'))
            ->assertOk()
            ->assertSee('Scan the QR code in ACAD 301 to start.')
            ->assertDontSee($this->room->qr_code);
    }

    public function test_pending_request_does_not_let_the_requester_start_a_session(): void
    {
        $this->pendingRequest();

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code])
            ->assertRedirect(route('faculty.sessions.scan', $this->room->qr_code))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_approval_cannot_be_used_after_the_class_time_is_over(): void
    {
        $this->pendingRequest(['status' => 'approved']);

        $this->travelTo('2026-10-12 10:30:00');

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code])
            ->assertRedirect(route('faculty.sessions.scan', $this->room->qr_code))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_free_room_approval_starts_a_temporary_session_while_the_room_stays_free(): void
    {
        $freeRoom = Room::factory()->create();

        // A class is scheduled in the free room later in the day.
        ClassSession::factory()->create([
            'room_id' => $freeRoom->id,
            'faculty_id' => $this->scheduledFaculty->id,
            'day' => 'M',
            'start_time' => '13:00',
            'end_time' => '15:00',
        ]);

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $freeRoom->id,
                'reason' => 'Meeting with advisees.',
            ]);

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.sessions.store'), ['code' => $freeRoom->qr_code])
            ->assertRedirect(route('faculty.dashboard'));

        $this->assertDatabaseHas('room_sessions', [
            'room_id' => $freeRoom->id,
            'faculty_id' => $this->requester->id,
            'class_session_id' => null,
            'status' => RoomSession::STATUS_ACTIVE,
        ]);
    }

    public function test_free_room_approval_does_not_cover_a_class_that_has_started(): void
    {
        $freeRoom = Room::factory()->create();

        ClassSession::factory()->create([
            'room_id' => $freeRoom->id,
            'faculty_id' => $this->scheduledFaculty->id,
            'day' => 'M',
            'start_time' => '13:00',
            'end_time' => '15:00',
        ]);

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $freeRoom->id,
                'reason' => 'Meeting with advisees.',
            ]);

        $this->travelTo('2026-10-12 13:30:00');

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.sessions.store'), ['code' => $freeRoom->qr_code])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_a_second_request_is_refused_while_an_approval_is_still_valid(): void
    {
        $this->pendingRequest(['status' => 'approved']);

        $this->actingAs($this->requesterUser)
            ->post(route('faculty.temporary-requests.store'), [
                'room_id' => $this->room->id,
                'reason' => 'Again.',
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseCount('temporary_classroom_requests', 1);
    }

    /**
     * A request from the requester for the room, made during the scheduled class.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function pendingRequest(array $attributes = []): TemporaryClassroomRequest
    {
        return TemporaryClassroomRequest::factory()->create([
            'requester_id' => $this->requester->id,
            'requester_class_session_id' => $this->requesterClass->id,
            'room_id' => $this->room->id,
            'scheduled_class_session_id' => $this->scheduledClass->id,
            'scheduled_faculty_id' => $this->scheduledFaculty->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array{0: User, 1: Faculty}
     */
    private function createFacultyMember(): array
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();

        return [$user, $faculty];
    }
}
