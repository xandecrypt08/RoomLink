<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomIssue;
use App\Models\RoomMaintenance;
use App\Models\RoomSession;
use App\Models\User;
use App\Notifications\RoomIssueReported;
use App\Notifications\RoomIssueUpdated;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Report Room Issue (RLNK-31), Report Damaged QR Code (RLNK-32), Update
 * Maintenance Status (RLNK-33) and Room Availability Management (RLNK-34).
 */
class RoomIssueTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private User $teacher;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-23 08:30')); // a Monday
        $this->admin = User::factory()->admin()->create();
        $this->teacher = User::factory()->faculty()->create();
        $this->room = Room::factory()->create(['room_name' => 'COMLAB 1A']);
    }

    public function test_faculty_report_an_issue_and_active_administrators_are_notified(): void
    {
        Notification::fake();
        $inactive = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($this->teacher)->get(route('faculty.issues.create', ['room' => $this->room->id]))->assertOk()->assertSee('COMLAB 1A');

        $this->actingAs($this->teacher)
            ->post(route('faculty.issues.store'), ['room_id' => $this->room->id, 'category' => 'aircon', 'description' => 'Not cooling'])
            ->assertRedirect(route('faculty.issues.index'));

        $issue = RoomIssue::sole();
        $this->assertSame([$this->room->id, 'aircon', 'Not cooling', $this->teacher->id, 'pending'], [$issue->room_id, $issue->category, $issue->description, $issue->reported_by, $issue->status]);
        $this->assertTrue($issue->created_at->isSameDay(now()));

        Notification::assertSentTo($this->admin, RoomIssueReported::class);
        Notification::assertNotSentTo($inactive, RoomIssueReported::class);

        $this->actingAs($this->teacher)->get(route('faculty.issues.index'))->assertSee('Not cooling')->assertSee('Pending');
    }

    public function test_an_issue_needs_a_room_a_category_and_a_description(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('faculty.issues.store'), ['category' => 'furniture'])
            ->assertSessionHasErrors(['room_id', 'description']);

        $this->actingAs($this->teacher)
            ->post(route('faculty.issues.store'), ['room_id' => $this->room->id, 'category' => 'ghosts', 'description' => 'x'])
            ->assertSessionHasErrors('category');
    }

    public function test_faculty_only_see_their_own_reports(): void
    {
        RoomIssue::factory()->create(['description' => 'Someone else reported this']);

        $this->actingAs($this->teacher)->get(route('faculty.issues.index'))->assertDontSee('Someone else reported this');
    }

    public function test_a_damaged_qr_code_can_be_reported_once_with_a_replacement_request(): void
    {
        $report = ['room_id' => $this->room->id, 'category' => 'qr_code', 'qr_replacement_requested' => '1'];

        $this->actingAs($this->teacher)->post(route('faculty.issues.store'), $report)->assertSessionHasNoErrors();
        $this->assertTrue(RoomIssue::sole()->qr_replacement_requested);

        $notification = $this->admin->notifications()->sole();
        $this->assertStringContainsString('A replacement QR code was requested.', $notification->data['message']);

        $this->actingAs($this->admin)->get(route('admin.notifications.index'))->assertOk()->assertSee('COMLAB 1A');

        $this->actingAs($this->teacher)->post(route('faculty.issues.store'), $report)->assertSessionHasErrors('category');
        $this->assertSame(1, RoomIssue::count());
    }

    public function test_the_scan_page_offers_to_report_an_unreadable_code(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('faculty.sessions.scan', 'ROOM-UNKNOWN'))
            ->assertSee(route('faculty.issues.create', ['category' => 'qr_code']), false);
    }

    public function test_an_administrator_takes_an_issue_through_every_status(): void
    {
        $issue = RoomIssue::factory()->create(['room_id' => $this->room->id, 'reported_by' => $this->teacher->id]);

        $this->actingAs($this->admin)->get(route('room-issues.index'))->assertSee($issue->description);

        foreach (['acknowledged', 'in_progress', 'resolved'] as $status) {
            $this->actingAs($this->admin)
                ->patch(route('room-issues.update', $issue), ['status' => $status, 'assigned_to' => 'Physical Plant', 'note' => "Now {$status}"])
                ->assertSessionHas('success');
        }

        $issue->refresh();
        $this->assertTrue($issue->isResolved());
        $this->assertNotNull($issue->resolved_at);
        $this->assertSame('Physical Plant', $issue->assigned_to);
        $this->assertSame(['pending', 'acknowledged', 'in_progress', 'resolved'], $issue->updates()->orderBy('id')->pluck('status')->all());
        $this->assertSame(3, $this->teacher->notifications()->where('type', RoomIssueUpdated::class)->count());

        $this->actingAs($this->admin)->get(route('room-issues.show', $issue))->assertOk()->assertSee('Now in_progress');
        $this->actingAs($this->teacher)->get(route('faculty.notifications.index'))->assertOk()->assertSee('is now Resolved');
    }

    public function test_statuses_cannot_be_skipped_or_reversed(): void
    {
        $issue = RoomIssue::factory()->create();

        $this->actingAs($this->admin)->patch(route('room-issues.update', $issue), ['status' => 'resolved'])->assertSessionHas('error');
        $this->actingAs($this->admin)->patch(route('room-issues.update', $issue), ['status' => 'acknowledged'])->assertSessionHas('success');
        $this->actingAs($this->admin)->patch(route('room-issues.update', $issue), ['status' => 'pending'])->assertSessionHas('error');

        $this->assertSame('acknowledged', $issue->fresh()->status);
    }

    public function test_opening_an_issue_marks_its_notification_read(): void
    {
        $this->actingAs($this->teacher)->post(route('faculty.issues.store'), ['room_id' => $this->room->id, 'category' => 'other', 'description' => 'Leak']);

        $this->actingAs($this->admin)->get(route('room-issues.show', RoomIssue::sole()))->assertOk();

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
    }

    public function test_replacing_a_damaged_code_retires_the_old_one_and_keeps_the_issue_open(): void
    {
        $issue = RoomIssue::factory()->create(['room_id' => $this->room->id, 'category' => 'qr_code']);
        $oldCode = $this->room->qr_code;

        $this->actingAs($this->admin)->post(route('room-issues.replace-qr', $issue))->assertSessionHas('success');

        $this->assertNotSame($oldCode, $this->room->fresh()->qr_code);
        $this->assertDatabaseHas('retired_qr_codes', ['qr_code' => $oldCode]);
        $this->assertSame('in_progress', $issue->fresh()->status);

        $other = RoomIssue::factory()->create(['category' => 'furniture']);
        $this->actingAs($this->admin)->post(route('room-issues.replace-qr', $other))->assertNotFound();
    }

    public function test_a_room_under_maintenance_refuses_sessions_until_restored(): void
    {
        $faculty = Faculty::factory()->for($this->teacher)->create();
        ClassSession::factory()->create(['room_id' => $this->room->id, 'faculty_id' => $faculty->id, 'day' => 'M']);
        $start = fn () => $this->actingAs($this->teacher)->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code]);

        $this->actingAs($this->admin)->post(route('rooms.maintenance.store', $this->room), ['reason' => 'Rewiring'])->assertSessionHas('success');
        $start();
        $this->assertSame(0, RoomSession::count());

        $this->actingAs($this->admin)->delete(route('rooms.maintenance.destroy', $this->room))->assertSessionHas('success');
        $start();
        $this->assertSame(1, RoomSession::active()->count());

        $record = RoomMaintenance::sole();
        $this->assertSame(['Rewiring', $this->admin->id, $this->admin->id], [$record->reason, $record->started_by, $record->ended_by]);
        $this->assertNotNull($record->ended_at);
    }

    public function test_a_room_restored_during_a_session_is_occupied(): void
    {
        RoomSession::factory()->for($this->room)->create();
        $this->room->update(['status' => 'occupied']);

        $this->actingAs($this->admin)->post(route('rooms.maintenance.store', $this->room));
        $this->assertSame('maintenance', $this->room->fresh()->status);

        $this->actingAs($this->admin)->delete(route('rooms.maintenance.destroy', $this->room));
        $this->assertSame('occupied', $this->room->fresh()->status);
    }

    public function test_maintenance_set_on_the_room_form_is_recorded_too(): void
    {
        $form = ['floor_id' => $this->room->floor_id, 'room_name' => 'COMLAB 1A', 'capacity' => 40];

        $this->actingAs($this->admin)->put(route('rooms.update', $this->room), [...$form, 'status' => 'maintenance']);
        $this->actingAs($this->admin)->put(route('rooms.update', $this->room), [...$form, 'status' => 'maintenance', 'capacity' => 30]);
        $this->assertNull(RoomMaintenance::sole()->ended_at);

        $this->actingAs($this->admin)->put(route('rooms.update', $this->room), [...$form, 'status' => 'available']);
        $this->assertNotNull(RoomMaintenance::sole()->ended_at);
    }

    public function test_resolving_an_issue_can_restore_its_room(): void
    {
        $issue = RoomIssue::factory()->create(['room_id' => $this->room->id, 'status' => 'in_progress']);
        $this->actingAs($this->admin)->post(route('rooms.maintenance.store', $this->room), ['room_issue_id' => $issue->id]);

        $this->actingAs($this->admin)->get(route('room-issues.show', $issue))->assertSee('Restore COMLAB 1A');
        $this->actingAs($this->admin)->patch(route('room-issues.update', $issue), ['status' => 'resolved', 'restore_room' => '1']);

        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertSame($issue->id, RoomMaintenance::sole()->room_issue_id);
    }

    public function test_an_issue_of_another_room_cannot_be_linked_to_a_maintenance(): void
    {
        $this->actingAs($this->admin)
            ->post(route('rooms.maintenance.store', $this->room), ['room_issue_id' => RoomIssue::factory()->create()->id])
            ->assertSessionHasErrors('room_issue_id');
    }

    public function test_a_room_with_issues_cannot_be_deleted(): void
    {
        RoomIssue::factory()->create(['room_id' => $this->room->id]);

        $this->actingAs($this->admin)->delete(route('rooms.destroy', $this->room))->assertSessionHas('error');

        $this->assertModelExists($this->room);
    }

    public function test_room_pages_show_the_maintenance_controls(): void
    {
        $this->actingAs($this->admin)->get(route('rooms.show', $this->room))->assertOk()->assertSee('Mark Under Maintenance');

        $this->room->startMaintenance('Painting');

        $this->actingAs($this->admin)->get(route('rooms.show', $this->room))->assertSee('Restore Room')->assertSee('Painting');
        $this->actingAs($this->admin)->get(route('classrooms.index'))->assertSee(route('rooms.maintenance.destroy', $this->room));
    }

    public function test_each_role_is_kept_to_its_own_pages(): void
    {
        $issue = RoomIssue::factory()->create();

        $this->actingAs($this->teacher)->get(route('room-issues.index'))->assertForbidden();
        $this->actingAs($this->teacher)->patch(route('room-issues.update', $issue), ['status' => 'acknowledged'])->assertForbidden();
        $this->actingAs($this->teacher)->post(route('rooms.maintenance.store', $this->room))->assertForbidden();
        $this->actingAs(User::factory()->student()->create())->get(route('faculty.issues.create'))->assertForbidden();
    }
}
