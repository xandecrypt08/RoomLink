<?php

namespace Tests\Feature;

use App\Actions\EndForgottenSessions;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\User;
use App\Notifications\SessionEndedAutomatically;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ForgottenSessionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Room $room;

    private Faculty $faculty;

    private ClassSession $class;

    private RoomSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-23 08:00')); // a Monday
        $this->room = Room::factory()->create(['status' => 'occupied']);
        $this->faculty = Faculty::factory()->for(User::factory()->faculty())->create();
        $this->class = ClassSession::factory()->create(['room_id' => $this->room->id, 'faculty_id' => $this->faculty->id, 'day' => 'M', 'start_time' => '08:00:00', 'end_time' => '10:00:00']);
        $this->session = RoomSession::factory()->create(['room_id' => $this->room->id, 'faculty_id' => $this->faculty->id, 'class_session_id' => $this->class->id, 'started_at' => now()]);
    }

    public function test_a_session_is_left_alone_until_the_grace_period_is_over(): void
    {
        foreach (['09:59', '10:29'] as $time) {
            $this->travelTo(Carbon::parse("2026-11-23 {$time}"));
            $this->artisan('roomlink:end-forgotten-sessions')->assertSuccessful();
        }

        $this->assertTrue($this->session->fresh()->isActive());
    }

    public function test_a_forgotten_session_ends_when_its_class_did_and_releases_the_room(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 10:30'));
        $this->artisan('roomlink:end-forgotten-sessions')->assertSuccessful();
        $this->artisan('roomlink:end-forgotten-sessions')->assertSuccessful();

        $session = $this->session->fresh();
        $this->assertSame(['ended', '2026-11-23 10:00', EndForgottenSessions::REASON], [$session->status, $session->ended_at->format('Y-m-d H:i'), $session->reason]);
        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertSame(1, $this->faculty->user->notifications()->where('type', SessionEndedAutomatically::class)->count());
    }

    public function test_a_session_from_an_earlier_day_ends_on_that_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 07:00'));
        $this->artisan('roomlink:end-forgotten-sessions');

        $this->assertSame('2026-11-23 10:00', $this->session->fresh()->ended_at->format('Y-m-d H:i'));
    }

    public function test_a_temporary_session_ends_after_four_hours(): void
    {
        $this->session->update(['class_session_id' => null]);

        $this->travelTo(Carbon::parse('2026-11-23 11:59'));
        $this->artisan('roomlink:end-forgotten-sessions');
        $this->assertTrue($this->session->fresh()->isActive());

        $this->travelTo(Carbon::parse('2026-11-23 12:00'));
        $this->artisan('roomlink:end-forgotten-sessions');
        $this->assertSame('12:00', $this->session->fresh()->ended_at->format('H:i'));
    }

    public function test_the_next_class_can_start_even_if_the_scheduler_is_not_running(): void
    {
        $next = Faculty::factory()->for(User::factory()->faculty())->create();
        ClassSession::factory()->create(['room_id' => $this->room->id, 'faculty_id' => $next->id, 'day' => 'M', 'start_time' => '13:00:00', 'end_time' => '15:00:00']);

        $this->travelTo(Carbon::parse('2026-11-23 13:00'));
        $this->actingAs($next->user)->post(route('faculty.sessions.store'), ['code' => $this->room->qr_code])->assertRedirect(route('faculty.dashboard'));

        $this->assertFalse($this->session->fresh()->isActive());
        $this->assertSame($next->id, RoomSession::active()->sole()->faculty_id);
        $this->assertSame('occupied', $this->room->fresh()->status);
    }

    public function test_maintenance_set_meanwhile_stays(): void
    {
        $this->room->startMaintenance('Leak');

        $this->travelTo(Carbon::parse('2026-11-23 12:00'));
        $this->artisan('roomlink:end-forgotten-sessions');

        $this->assertSame('maintenance', $this->room->fresh()->status);
    }

    public function test_every_page_starts_with_a_skip_link(): void
    {
        $this->actingAs($this->faculty->user)
            ->get(route('faculty.dashboard'))
            ->assertSee('href="#main-content" class="skip-link"', false)
            ->assertSee('id="main-content"', false);
    }
}
