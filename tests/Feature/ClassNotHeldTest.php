<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ClassNotHeldTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Faculty $faculty;

    private Room $room;

    /**
     * Monday 08:00–10:00.
     */
    private ClassSession $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Monday 2026-10-12, 07:30 — before the 08:00-10:00 Monday class.
        $this->travelTo(Carbon::parse('2026-10-12 07:30:00'));

        $this->user = User::factory()->faculty()->create();
        $this->faculty = Faculty::factory()->for($this->user)->create();
        $this->room = Room::factory()->create();
        $this->schedule = ClassSession::factory()->for($this->faculty)->create([
            'room_id' => $this->room->id,
        ]);
    }

    private function markNotHeld(array $payload = ['reason' => 'Faculty on official leave'], ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->user)
            ->post(route('faculty.not-held.store', $this->schedule), $payload);
    }

    public function test_form_shows_the_class(): void
    {
        $this->actingAs($this->user)
            ->get(route('faculty.not-held.create', $this->schedule))
            ->assertOk()
            ->assertSee($this->schedule->subject->subject_code)
            ->assertSee('Mark as Not Held');
    }

    public function test_class_not_started_yet_is_recorded_as_not_held(): void
    {
        $response = $this->markNotHeld();

        $response->assertRedirect(route('faculty.dashboard'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('room_sessions', [
            'class_session_id' => $this->schedule->id,
            'room_id' => $this->room->id,
            'faculty_id' => $this->faculty->id,
            'status' => RoomSession::STATUS_NOT_HELD,
            'started_at' => '2026-10-12 08:00:00',
            'ended_at' => '2026-10-12 07:30:00',
            'reason' => 'Faculty on official leave',
            'active_room_id' => null,
        ]);
        $this->assertSame('available', $this->room->fresh()->status);
    }

    public function test_running_class_is_ended_as_not_held_and_the_room_released(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 08:05:00'));
        $this->actingAs($this->user)->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code]);
        $this->assertSame('occupied', $this->room->fresh()->status);

        $this->travelTo(Carbon::parse('2026-10-12 08:20:00'));
        $this->markNotHeld(['reason' => 'Students on field trip']);

        $this->assertDatabaseCount('room_sessions', 1);
        $this->assertDatabaseHas('room_sessions', [
            'class_session_id' => $this->schedule->id,
            'status' => RoomSession::STATUS_NOT_HELD,
            'ended_at' => '2026-10-12 08:20:00',
            'reason' => 'Students on field trip',
        ]);
        $this->assertSame('available', $this->room->fresh()->status);
    }

    public function test_class_marked_not_held_cannot_be_started_later(): void
    {
        $this->markNotHeld();
        $this->travelTo(Carbon::parse('2026-10-12 08:05:00'));

        $response = $this->actingAs($this->user)
            ->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code]);

        $response->assertRedirect(route('faculty.sessions.scan', $this->room->qr_code));
        $response->assertSessionHas('error', "You marked {$this->schedule->subject->subject_code} as not held today, so it can't be started.");
        $this->assertSame('available', $this->room->fresh()->status);
    }

    public function test_class_can_only_be_marked_once_a_day(): void
    {
        $this->markNotHeld();

        $this->markNotHeld()
            ->assertSessionHas('error', "{$this->schedule->subject->subject_code} is already marked as not held today.");

        $this->assertDatabaseCount('room_sessions', 1);
    }

    public function test_class_already_held_today_cannot_be_marked(): void
    {
        RoomSession::factory()->for($this->room)->for($this->faculty)->ended()->create([
            'class_session_id' => $this->schedule->id,
            'started_at' => '2026-10-12 06:00:00',
            'ended_at' => '2026-10-12 07:00:00',
        ]);

        $this->markNotHeld()
            ->assertSessionHas('error', "{$this->schedule->subject->subject_code} was already held today and cannot be marked as not held.");

        $this->assertSame(0, RoomSession::where('status', RoomSession::STATUS_NOT_HELD)->count());
    }

    public function test_last_weeks_not_held_does_not_block_today(): void
    {
        RoomSession::factory()->for($this->room)->for($this->faculty)->create([
            'class_session_id' => $this->schedule->id,
            'status' => RoomSession::STATUS_NOT_HELD,
            'started_at' => '2026-10-05 08:00:00',
            'ended_at' => '2026-10-05 07:00:00',
        ]);

        $this->markNotHeld()->assertSessionHas('success');
    }

    public function test_class_not_scheduled_today_cannot_be_marked(): void
    {
        $this->schedule->update(['day' => 'T']);

        $this->markNotHeld()
            ->assertSessionHas('error', "{$this->schedule->subject->subject_code} is not scheduled today.");

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_reason_is_required(): void
    {
        $this->markNotHeld(['reason' => ''])->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_faculty_cannot_mark_another_faculty_members_class(): void
    {
        $otherUser = User::factory()->faculty()->create();
        Faculty::factory()->for($otherUser)->create();

        $this->actingAs($otherUser)->get(route('faculty.not-held.create', $this->schedule))->assertForbidden();
        $this->markNotHeld(user: $otherUser)->assertForbidden();

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_dashboard_shows_not_held_status_and_hides_the_action(): void
    {
        $this->actingAs($this->user)->get(route('faculty.dashboard'))
            ->assertSee(route('faculty.not-held.create', $this->schedule));

        $this->markNotHeld();

        $this->actingAs($this->user)->get(route('faculty.dashboard'))
            ->assertSee('Not Held')
            ->assertDontSee(route('faculty.not-held.create', $this->schedule));
    }
}
