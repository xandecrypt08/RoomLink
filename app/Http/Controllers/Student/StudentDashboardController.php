<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\ClassSession;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();

        $rooms = Room::with([
            'floor.building.campus',
            'facilities',
            'classSessions' => fn ($query) => $query
                ->with('subject')
                ->inCurrentTerm()
                ->where('day', ClassSession::dayCodeFor($now))
                ->where('status', 'active')
                ->orderBy('start_time'),
        ])
            ->orderBy('room_name')
            ->get();

        return view('student.dashboard', [
            'student' => $request->user(),
            'rooms' => $rooms,
            'availability' => $rooms->mapWithKeys(fn (Room $room) => [$room->id => $this->availability($room, $now)]),
            'campuses' => Campus::has('buildings')->orderBy('campus_name')->pluck('campus_name', 'id'),
            'totalRooms' => $rooms->count(),
            'availableRooms' => $rooms->where('status', 'available')->count(),
            'occupiedRooms' => $rooms->where('status', 'occupied')->count(),
            'maintenanceRooms' => $rooms->where('status', 'maintenance')->count(),
        ]);
    }

    /**
     * Whether a room is free now, from its status and today's classes. A room
     * whose class is scheduled now but hasn't started isn't free.
     *
     * @return array{free: bool, note: string|null}
     */
    private function availability(Room $room, CarbonInterface $now): array
    {
        if ($room->status !== 'available') {
            return ['free' => false, 'note' => null];
        }

        $ongoing = $room->classSessions->first(fn (ClassSession $class) => $class->isOngoingAt($now));

        if ($ongoing !== null) {
            return ['free' => false, 'note' => "{$ongoing->subject->subject_code} scheduled until {$ongoing->end_time->format('g:i A')}"];
        }

        $next = $room->classSessions->first(fn (ClassSession $class) => $class->start_time->format('H:i:s') > $now->format('H:i:s'));

        return ['free' => true, 'note' => $next === null ? 'Free for the rest of today' : "Free until {$next->start_time->format('g:i A')}"];
    }
}
