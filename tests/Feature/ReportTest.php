<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Room;
use App\Models\RoomIssue;
use App\Models\RoomSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Classroom Utilization Report (RLNK-35), Room Issue & Maintenance Report
 * (RLNK-36) and Administrator Dashboard (RLNK-37).
 */
class ReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-23 12:00'));
        $this->admin = User::factory()->admin()->create();
        $this->room = Room::factory()->create(['room_name' => 'COMLAB 1A']);
    }

    /**
     * An ended 90-minute session, yesterday unless overridden.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function usage(array $overrides = []): RoomSession
    {
        return RoomSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => RoomSession::STATUS_ENDED,
            'started_at' => '2026-11-22 08:00:00',
            'ended_at' => '2026-11-22 09:30:00',
            ...$overrides,
        ]);
    }

    public function test_utilization_shows_who_used_each_classroom_and_for_how_long(): void
    {
        $class = ClassSession::factory()->create(['room_id' => $this->room->id]);
        $this->usage(['class_session_id' => $class->id, 'faculty_id' => $class->faculty_id]);

        $this->actingAs($this->admin)
            ->get(route('reports.utilization'))
            ->assertOk()
            ->assertSee('COMLAB 1A')
            ->assertSee($class->faculty->full_name)
            ->assertSee($class->subject->subject_code)
            ->assertSee($class->section->section_name)
            ->assertSee('1h 30m');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function places(): array
    {
        return ['campus' => ['campus'], 'building' => ['building'], 'room' => ['room']];
    }

    #[DataProvider('places')]
    public function test_utilization_is_filtered_by_place(string $place): void
    {
        $mine = $this->usage();
        $this->usage(['room_id' => Room::factory()->create()->id]);
        $id = ['campus' => $this->room->floor->building->campus_id, 'building' => $this->room->floor->building_id, 'room' => $this->room->id][$place];

        $this->actingAs($this->admin)
            ->get(route('reports.utilization', [$place => $id]))
            ->assertViewHas('sessions', fn ($sessions) => $sessions->pluck('id')->all() === [$mine->id]);
    }

    public function test_utilization_is_filtered_by_date_including_both_days(): void
    {
        $first = $this->usage(['started_at' => '2026-10-01 00:00:00', 'ended_at' => '2026-10-01 01:00:00']);
        $last = $this->usage(['started_at' => '2026-10-05 23:30:00', 'ended_at' => '2026-10-05 23:59:00']);
        $this->usage(['started_at' => '2026-10-06 00:00:00', 'ended_at' => '2026-10-06 01:00:00']);

        foreach ([['2026-10-01', '2026-10-05'], ['2026-10-05', '2026-10-01']] as [$from, $to]) {
            $this->actingAs($this->admin)
                ->get(route('reports.utilization', ['from' => $from, 'to' => $to]))
                ->assertViewHas('sessions', fn ($sessions) => $sessions->pluck('id')->sort()->values()->all() === [$first->id, $last->id]);
        }
    }

    public function test_classes_not_held_are_not_usage_and_running_ones_count_so_far(): void
    {
        $this->usage(['status' => RoomSession::STATUS_NOT_HELD]);
        $this->usage(['status' => RoomSession::STATUS_ACTIVE, 'started_at' => now()->subMinutes(45), 'ended_at' => null]);

        $this->actingAs($this->admin)
            ->get(route('reports.utilization'))
            ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1)
            ->assertViewHas('minutesByRoom', fn ($rooms) => $rooms->sum('minutes') === 45);
    }

    public function test_bad_filters_still_show_a_report(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.utilization', ['from' => 'yesterday', 'to' => ['x'], 'room' => 'abc', 'campus' => 999]))
            ->assertOk();
    }

    public function test_utilization_exports_every_matching_session(): void
    {
        $class = ClassSession::factory()->create(['room_id' => $this->room->id]);
        $this->usage(['class_session_id' => $class->id, 'faculty_id' => $class->faculty_id]);
        RoomSession::factory()->count(25)->create(['room_id' => $this->room->id, 'status' => RoomSession::STATUS_ENDED, 'started_at' => now()->subDay(), 'ended_at' => now()->subDay()->addHour()]);

        $response = $this->actingAs($this->admin)->get(route('reports.utilization', ['export' => 1]))->assertOk()->assertDownload('classroom-utilization-2026-10-25-to-2026-11-23.csv');
        $this->actingAs($this->admin)->get(route('reports.utilization'))->assertViewHas('sessions', fn ($sessions) => $sessions->count() === 20);
        $this->actingAs($this->admin)->get(route('reports.utilization', ['print' => 1]))->assertViewHas('sessions', fn ($sessions) => $sessions->count() === 26);

        $lines = explode("\n", trim($response->streamedContent()));

        $this->assertCount(27, $lines);
        $this->assertStringContainsString('Classroom,Faculty,Subject', $lines[0]);
        $this->assertStringContainsString($class->subject->subject_code, implode("\n", $lines));
        $this->assertStringContainsString(',90', implode("\n", $lines));
    }

    public function test_the_issue_report_shows_filters_and_exports(): void
    {
        $resolved = RoomIssue::factory()->create(['room_id' => $this->room->id, 'category' => 'aircon', 'status' => 'resolved', 'resolved_at' => '2026-11-23 10:00:00']);
        RoomIssue::factory()->create(['category' => 'furniture']);

        $this->actingAs($this->admin)
            ->get(route('reports.maintenance', ['category' => 'aircon', 'status' => 'resolved', 'room' => $this->room->id]))
            ->assertOk()
            ->assertViewHas('records', fn ($records) => $records->pluck('id')->all() === [$resolved->id])
            ->assertSee('Air Conditioning or Fan')
            ->assertSee('Nov 23, 2026');

        $csv = $this->actingAs($this->admin)->get(route('reports.maintenance', ['export' => 1, 'category' => 'aircon']))->streamedContent();
        $this->assertStringContainsString('COMLAB 1A', $csv);
        $this->assertStringContainsString('2026-11-23 10:00', $csv);
        $this->assertStringNotContainsString('Furniture', $csv);
    }

    public function test_the_maintenance_history_includes_periods_that_overlap_the_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 09:00'));
        $this->actingAs($this->admin)->post(route('rooms.maintenance.store', $this->room), ['reason' => 'Repainting']);
        $this->travelTo(Carbon::parse('2026-10-20 09:00'));
        $this->actingAs($this->admin)->delete(route('rooms.maintenance.destroy', $this->room));

        $report = fn (string $from, string $to) => $this->actingAs($this->admin)->get(route('reports.maintenance', ['type' => 'maintenance', 'from' => $from, 'to' => $to]));

        $report('2026-10-10', '2026-10-12')->assertSee('Repainting')->assertSee('Restored');
        $report('2026-10-21', '2026-10-25')->assertDontSee('Repainting');

        $csv = $this->actingAs($this->admin)->get(route('reports.maintenance', ['type' => 'maintenance', 'from' => '2026-10-01', 'to' => '2026-10-31', 'export' => 1]))->streamedContent();
        $this->assertStringContainsString('Repainting', $csv);
    }

    public function test_typed_text_cannot_run_as_a_formula_in_an_export(): void
    {
        RoomIssue::factory()->create(['description' => '=HYPERLINK("http://evil.test")']);

        $csv = $this->actingAs($this->admin)->get(route('reports.maintenance', ['export' => 1]))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_the_dashboard_summarises_rooms_faculty_and_maintenance_live(): void
    {
        $class = ClassSession::factory()->create(['room_id' => $this->room->id, 'day' => 'M', 'start_time' => '11:00:00', 'end_time' => '13:00:00']);
        RoomSession::factory()->create(['room_id' => $this->room->id, 'faculty_id' => $class->faculty_id, 'class_session_id' => $class->id, 'started_at' => now()->subMinutes(30)]);
        $this->room->update(['status' => 'occupied']);
        Room::factory()->create(['room_name' => 'FREE ROOM']);
        Room::factory()->create(['room_name' => 'BROKEN ROOM'])->startMaintenance('Leaking roof');
        RoomIssue::factory()->create();

        foreach (['admin.dashboard', 'admin.dashboard.live'] as $page) {
            $this->actingAs($this->admin)
                ->get(route($page))
                ->assertOk()
                ->assertSee($class->faculty->full_name)
                ->assertSee('FREE ROOM')
                ->assertSee('Leaking roof')
                ->assertSee($this->room->floor->building->campus->campus_name)
                ->assertViewHas('locatedSessions', fn ($sessions) => $sessions->count() === 1)
                ->assertViewHas('issueCounts', fn ($counts) => (int) $counts->get('pending') === 1)
                ->assertViewHas('minutesToday', 30);
        }

        $this->assertStringNotContainsString('<html', $this->actingAs($this->admin)->get(route('admin.dashboard.live'))->getContent());
    }

    public function test_reports_are_for_administrators(): void
    {
        $faculty = User::factory()->faculty()->create();

        foreach (['reports.utilization', 'reports.maintenance', 'admin.dashboard.live'] as $page) {
            $this->actingAs($faculty)->get(route($page))->assertForbidden();
        }
    }
}
