<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use App\Models\ClassSession;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RoomIssue;
use App\Models\RoomMaintenance;
use App\Models\RoomSession;
use App\Support\SelectedTerm;
use Illuminate\View\View;

/**
 * Classroom overview across all campuses (RLNK-37). The summaries refresh by
 * themselves while the page is open (public/js/dashboard.js).
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $recentSessions = ClassSession::with([
            'room.floor.building',
            'faculty',
            'subject',
            'section',
        ])
            ->inTerm(SelectedTerm::get())
            ->orderedByWeek()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'totalCampuses' => Campus::count(),
            'totalBuildings' => Building::count(),
            'totalFloors' => Floor::count(),
            'totalRooms' => Room::count(),
            'recentSessions' => $recentSessions,
            ...$this->liveSummary(),
        ]);
    }

    /**
     * Only the live part of the page, for refreshing it.
     */
    public function live(): View
    {
        return view('admin.dashboard.live', $this->liveSummary());
    }

    /**
     * @return array<string, mixed>
     */
    private function liveSummary(): array
    {
        $now = now();
        $rooms = Room::with('floor.building.campus')->orderBy('room_name')->get();

        $activeSessions = RoomSession::active()
            ->with(['room.floor.building', 'faculty', 'classSession.subject', 'classSession.section'])
            ->latest('started_at')
            ->get();

        $todaysSessions = RoomSession::query()
            ->whereBetween('started_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->where('status', '!=', RoomSession::STATUS_NOT_HELD)
            ->get(['id', 'room_id', 'status', 'started_at', 'ended_at']);

        return [
            'rooms' => $rooms,
            'activeSessions' => $activeSessions,
            // The same rule as the Faculty Locator page.
            'locatedSessions' => $activeSessions->filter(fn (RoomSession $session) => $session->isLocatableAt($now))->values(),
            'openMaintenances' => RoomMaintenance::with(['room', 'roomIssue'])->whereNull('ended_at')->latest('started_at')->get(),
            'issueCounts' => RoomIssue::open()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'roomsByCampus' => $rooms->groupBy(fn (Room $room) => $room->floor?->building?->campus?->campus_name ?? 'No campus')->sortKeys(),
            'sessionsToday' => $todaysSessions->count(),
            'minutesToday' => $todaysSessions->sum(fn (RoomSession $session) => $session->durationMinutes()),
            'roomsUsedToday' => $todaysSessions->unique('room_id')->count(),
            'updatedAt' => $now,
        ];
    }
}
