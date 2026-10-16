<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\TemporaryClassroomRequest;
use App\Models\User;
use App\Notifications\TemporaryClassroomRequestReviewed;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FacultyNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('faculty.notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_student_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('faculty.notifications.index'))
            ->assertForbidden();
    }

    public function test_approving_a_pending_request_notifies_the_requester(): void
    {
        Notification::fake();

        [$requesterUser, $requester] = $this->createFacultyMember();

        $request = TemporaryClassroomRequest::factory()->create(['requester_id' => $requester->id]);

        $request->update(['status' => 'approved']);

        Notification::assertSentTo(
            $requesterUser,
            TemporaryClassroomRequestReviewed::class,
            fn (TemporaryClassroomRequestReviewed $notification) => $notification->temporaryClassroomRequest->is($request)
        );
    }

    public function test_rejection_message_names_the_room(): void
    {
        [$requesterUser, $requester] = $this->createFacultyMember();

        $request = TemporaryClassroomRequest::factory()->create([
            'requester_id' => $requester->id,
            'room_id' => Room::factory()->create(['room_name' => 'Room 110'])->id,
        ]);

        $request->update(['status' => 'rejected']);

        $this->assertSame(
            'Your request to use Room 110 was rejected.',
            $requesterUser->notifications()->sole()->data['message']
        );
    }

    public function test_other_changes_do_not_notify_the_requester(): void
    {
        Notification::fake();

        [, $requester] = $this->createFacultyMember();

        $request = TemporaryClassroomRequest::factory()->create(['requester_id' => $requester->id]);

        $request->update(['reason' => 'Updated reason.']);
        $request->update(['status' => 'cancelled']);

        Notification::assertNothingSent();
    }

    public function test_faculty_sees_their_notifications_and_they_are_marked_read(): void
    {
        [$user, $requester] = $this->createFacultyMember();

        TemporaryClassroomRequest::factory()->create([
            'requester_id' => $requester->id,
            'room_id' => Room::factory()->create(['room_name' => 'Room 410'])->id,
        ])->update(['status' => 'approved']);

        $this->assertCount(1, $user->unreadNotifications);

        $this->actingAs($user)
            ->get(route('faculty.notifications.index'))
            ->assertOk()
            ->assertSee('Your request to use Room 410 was approved.')
            ->assertSee('New');

        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }

    public function test_faculty_only_sees_their_own_notifications(): void
    {
        [$user] = $this->createFacultyMember();
        [, $otherRequester] = $this->createFacultyMember();

        TemporaryClassroomRequest::factory()->create([
            'requester_id' => $otherRequester->id,
            'room_id' => Room::factory()->create(['room_name' => 'Room 999'])->id,
        ])->update(['status' => 'approved']);

        $this->actingAs($user)
            ->get(route('faculty.notifications.index'))
            ->assertOk()
            ->assertSee('You have no notifications.')
            ->assertDontSee('Room 999');
    }

    public function test_unread_count_is_shown_in_the_top_bar(): void
    {
        [$user, $requester] = $this->createFacultyMember();

        TemporaryClassroomRequest::factory()->create(['requester_id' => $requester->id])
            ->update(['status' => 'approved']);

        $this->actingAs($user)
            ->get(route('faculty.dashboard'))
            ->assertOk()
            ->assertSee(route('faculty.notifications.index'))
            ->assertSee('<span class="notification-count">1</span>', false);
    }

    public function test_room_requests_badge_counts_only_room_request_notifications(): void
    {
        [$user, $requester] = $this->createFacultyMember();

        TemporaryClassroomRequest::factory()->create(['requester_id' => $requester->id])
            ->update(['status' => 'approved']);

        $response = $this->actingAs($user)
            ->get(route('faculty.dashboard'))
            ->assertOk();

        $this->assertStringNotContainsString(
            '<span class="menu-badge">1</span>',
            $this->sidebarLink($response->getContent(), 'Room Requests')
        );

        $this->assertStringContainsString(
            '<span class="menu-badge">1</span>',
            $this->sidebarLink($response->getContent(), 'Notifications')
        );
    }

    public function test_mark_all_read(): void
    {
        [$user, $requester] = $this->createFacultyMember();

        TemporaryClassroomRequest::factory()->create(['requester_id' => $requester->id])
            ->update(['status' => 'approved']);

        $this->actingAs($user)
            ->post(route('faculty.notifications.read'))
            ->assertRedirect(route('faculty.notifications.index'));

        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }

    /**
     * Create a faculty login account and its linked Faculty record.
     *
     * @return array{0: User, 1: Faculty}
     */
    /**
     * The HTML of the sidebar link whose label is $label.
     */
    private function sidebarLink(string $html, string $label): string
    {
        preg_match('#<a\s[^>]*>\s*<span class="menu-icon">(?:(?!</a>).)*?</span>\s*<span>'.preg_quote($label, '#').'</span>.*?</a>#s', $html, $matches);

        $this->assertNotEmpty($matches, "Sidebar link [{$label}] not found.");

        return $matches[0];
    }

    private function createFacultyMember(): array
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();

        return [$user, $faculty];
    }
}
