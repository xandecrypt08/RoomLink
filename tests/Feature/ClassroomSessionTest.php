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

class ClassroomSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Faculty $faculty;

    private Room $room;

    private ClassSession $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Monday 2026-10-12, 08:05 — inside the default 08:00-10:00 Monday class.
        $this->travelTo(Carbon::parse('2026-10-12 08:05:00'));

        $this->user = User::factory()->faculty()->create();
        $this->faculty = Faculty::factory()->for($this->user)->create();
        $this->room = Room::factory()->create();
        $this->schedule = ClassSession::factory()->for($this->faculty)->create([
            'room_id' => $this->room->id,
        ]);
    }

    private function scan(?Room $room = null): TestResponse
    {
        return $this->actingAs($this->user)
            ->get(route('faculty.sessions.scan', ($room ?? $this->room)->qr_code));
    }

    private function start(?Room $room = null): TestResponse
    {
        return $this->actingAs($this->user)
            ->post(route('faculty.sessions.store'), ['code' => ($room ?? $this->room)->qr_code]);
    }

    public function test_scanning_the_room_qr_shows_the_scheduled_class_and_start_button(): void
    {
        $response = $this->scan();

        $response->assertOk();
        $response->assertViewHas('schedule', fn (?ClassSession $schedule) => $schedule?->is($this->schedule));
        $response->assertViewHas('blocker', null);
        $response->assertSee('Start Session');
        $response->assertSee($this->schedule->subject->subject_code);
    }

    public function test_starting_a_session_logs_it_against_the_schedule_and_occupies_the_room(): void
    {
        $response = $this->start();

        $response->assertRedirect(route('faculty.dashboard'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('room_sessions', [
            'room_id' => $this->room->id,
            'faculty_id' => $this->faculty->id,
            'class_session_id' => $this->schedule->id,
            'status' => RoomSession::STATUS_ACTIVE,
            'started_at' => '2026-10-12 08:05:00',
        ]);
        $this->assertSame('occupied', $this->room->fresh()->status);
    }

    public function test_class_can_be_started_shortly_before_its_scheduled_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 07:50:00'));

        $this->start()->assertRedirect(route('faculty.dashboard'));

        $this->assertDatabaseCount('room_sessions', 1);
    }

    public function test_class_cannot_be_started_too_early_or_after_it_ends(): void
    {
        foreach (['2026-10-12 07:30:00', '2026-10-12 10:00:00', '2026-10-13 08:05:00'] as $moment) {
            $this->travelTo(Carbon::parse($moment));

            $this->scan()->assertViewHas('blocker', fn (?string $blocker) => str_contains((string) $blocker, 'no class scheduled'));
            $this->start()->assertRedirect(route('faculty.sessions.scan', $this->room->qr_code));
        }

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_faculty_cannot_start_a_class_scheduled_for_someone_else(): void
    {
        $otherUser = User::factory()->faculty()->create();
        Faculty::factory()->for($otherUser)->create();

        $this->actingAs($otherUser)
            ->get(route('faculty.sessions.scan', $this->room->qr_code))
            ->assertViewHas('blocker', fn (?string $blocker) => str_contains((string) $blocker, $this->faculty->full_name))
            ->assertDontSee('Start Session');

        $this->actingAs($otherUser)
            ->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_room_under_maintenance_cannot_be_started(): void
    {
        $this->room->update(['status' => 'maintenance']);

        $this->scan()->assertViewHas('blocker', fn (?string $blocker) => str_contains((string) $blocker, 'under maintenance'));
        $this->start()->assertSessionHas('error');

        $this->assertDatabaseCount('room_sessions', 0);
    }

    public function test_room_occupied_by_another_faculty_cannot_be_started(): void
    {
        $occupant = RoomSession::factory()->for($this->room)->create();
        $this->room->update(['status' => 'occupied']);

        $this->scan()->assertViewHas('blocker', "{$this->room->room_name} is currently occupied by {$occupant->faculty->full_name}.");
        $this->start()->assertSessionHas('error');

        $this->assertDatabaseCount('room_sessions', 1);
    }

    public function test_faculty_must_end_their_current_session_before_starting_another(): void
    {
        $otherRoom = Room::factory()->create();
        RoomSession::factory()->for($otherRoom)->for($this->faculty)->create();

        $this->scan()->assertViewHas('blocker', fn (?string $blocker) => str_contains((string) $blocker, $otherRoom->room_name));
        $this->start()->assertSessionHas('error');

        $this->assertNull($this->room->fresh()->activeSession);
    }

    public function test_unknown_qr_code_is_reported(): void
    {
        $this->actingAs($this->user)
            ->get(route('faculty.sessions.scan', 'ROOM-DOESNOTEXIST'))
            ->assertOk()
            ->assertViewHas('room', null)
            ->assertSee('does not belong to any RoomLink classroom');
    }

    public function test_faculty_account_without_a_faculty_record_cannot_start(): void
    {
        $unlinked = User::factory()->faculty()->create();

        $this->actingAs($unlinked)
            ->get(route('faculty.sessions.scan', $this->room->qr_code))
            ->assertViewHas('blocker', fn (?string $blocker) => str_contains((string) $blocker, 'not linked'));
    }

    public function test_students_and_guests_cannot_start_sessions(): void
    {
        $this->get(route('faculty.sessions.scan', $this->room->qr_code))->assertRedirect(route('login'));

        $student = User::factory()->student()->create();
        $this->actingAs($student)
            ->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code])
            ->assertForbidden();
    }

    public function test_dashboard_shows_the_active_session_with_an_end_button(): void
    {
        $this->start();

        $response = $this->actingAs($this->user)->get(route('faculty.dashboard'));

        $response->assertViewHas('activeSession', fn (?RoomSession $session) => $session?->room->is($this->room));
        $response->assertSee('End Session');
    }

    public function test_ending_a_session_records_the_end_time_and_releases_the_room(): void
    {
        $this->start();
        $session = $this->room->activeSession;

        $this->travelTo(Carbon::parse('2026-10-12 09:55:00'));

        $response = $this->actingAs($this->user)->post(route('faculty.sessions.end', $session));

        $response->assertRedirect(route('faculty.dashboard'));
        $response->assertSessionHas('success', "Session ended at 9:55 AM. {$this->room->room_name} is now available.");
        $this->assertDatabaseHas('room_sessions', [
            'id' => $session->id,
            'status' => RoomSession::STATUS_ENDED,
            'ended_at' => '2026-10-12 09:55:00',
        ]);
        $this->assertSame('available', $this->room->fresh()->status);

        $this->actingAs($this->user)
            ->get(route('faculty.dashboard'))
            ->assertViewHas('activeSession', null);
    }

    public function test_faculty_cannot_end_someone_elses_session(): void
    {
        $session = RoomSession::factory()->for($this->room)->create();

        $this->actingAs($this->user)
            ->post(route('faculty.sessions.end', $session))
            ->assertForbidden();

        $this->assertTrue($session->fresh()->isActive());
    }

    public function test_ending_an_already_ended_session_shows_an_error(): void
    {
        $session = RoomSession::factory()->for($this->room)->for($this->faculty)->ended()->create();

        $this->actingAs($this->user)
            ->post(route('faculty.sessions.end', $session))
            ->assertRedirect(route('faculty.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_room_qr_code_opens_the_faculty_scan_page_at_app_url(): void
    {
        config(['app.url' => 'http://192.168.1.50:8000']);

        $admin = User::factory()->admin()->create();

        // The admin opens the page through a different address than APP_URL.
        $this->actingAs($admin)
            ->get('http://roomlink.test/admin/rooms/'.$this->room->id.'/qr')
            ->assertViewHas('qrData', 'http://192.168.1.50:8000/faculty/scan/'.$this->room->qr_code)
            // The address is encoded in the QR image only, not printed on the page.
            ->assertDontSee('http://192.168.1.50:8000/faculty/scan/', false)
            ->assertDontSee("Phones can't open this QR code yet", false);

        $this->actingAs($admin)
            ->get(route('rooms.qr.print', $this->room))
            ->assertViewHas('qrData', 'http://192.168.1.50:8000/faculty/scan/'.$this->room->qr_code);
    }

    public function test_qr_page_warns_when_app_url_only_works_on_this_computer(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['http://localhost:8000', 'http://127.0.0.1', 'http://roomlink.test'] as $appUrl) {
            config(['app.url' => $appUrl]);

            $this->actingAs($admin)
                ->get(route('rooms.qr', $this->room))
                ->assertSee("Phones can't open this QR code yet", false);
        }
    }

    public function test_faculty_who_scans_before_logging_in_returns_to_the_scanned_room(): void
    {
        $scanUrl = route('faculty.sessions.scan', $this->room->qr_code);

        $this->get($scanUrl)->assertRedirect(route('login'));

        $this->post(route('login.submit'), [
            'email' => $this->user->email,
            'password' => 'password',
        ])->assertRedirect($scanUrl);
    }

    public function test_student_who_scans_before_logging_in_goes_to_their_dashboard(): void
    {
        $student = User::factory()->student()->create();

        $this->get(route('faculty.sessions.scan', $this->room->qr_code));

        $this->post(route('login.submit'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('student.dashboard'));
    }
}
