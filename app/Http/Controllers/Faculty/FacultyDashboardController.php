<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\RoomSession;
use Illuminate\Support\Facades\Auth;

class FacultyDashboardController extends Controller
{
    public function index()
    {
        $faculty = Auth::user();

        $currentDay = ClassSession::dayCodeFor(now());

        $facultyRecord = $faculty->faculty;

        $todaySchedules = collect();

        $activeSession = $facultyRecord?->roomSessions()
            ->active()
            ->with(['room.floor.building', 'classSession.subject', 'classSession.section'])
            ->first();

        if ($facultyRecord) {
            $todaySchedules = ClassSession::with([
                'room.floor.building.campus',
                'subject',
                'section',
            ])
                ->where('faculty_id', $facultyRecord->id)
                ->where('day', $currentDay)
                ->where('status', 'active')
                ->orderBy('start_time')
                ->get();
        }

        /*
         * Today's session log for each scheduled class, so the schedule
         * table can show Not Held / Held and offer "Mark Not Held".
         */
        $todaysSessionStatuses = $facultyRecord === null ? collect() : RoomSession::query()
            ->where('faculty_id', $facultyRecord->id)
            ->whereNotNull('class_session_id')
            ->whereDate('started_at', now()->toDateString())
            ->get(['class_session_id', 'status'])
            ->groupBy('class_session_id')
            ->map(fn ($sessions) => $sessions->pluck('status'));

        $currentTime = now()->format('H:i:s');

        $currentClass = $todaySchedules->first(function (ClassSession $schedule) use ($currentTime) {
            return $schedule->start_time->format('H:i:s') <= $currentTime
                && $schedule->end_time->format('H:i:s') > $currentTime;
        });

        $nextClass = $todaySchedules->first(function (ClassSession $schedule) use ($currentTime) {
            return $schedule->start_time->format('H:i:s') > $currentTime;
        });

        return view('faculty.dashboard', compact(
            'faculty',
            'facultyRecord',
            'todaySchedules',
            'currentClass',
            'nextClass',
            'activeSession',
            'todaysSessionStatuses'
        ));
    }
}
