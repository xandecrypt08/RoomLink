<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use Illuminate\Support\Facades\Auth;

class FacultyDashboardController extends Controller
{
    public function index()
    {
        $faculty = Auth::user();

        $today = now()->format('D');

        $dayMap = [
            'Mon' => 'M',
            'Tue' => 'T',
            'Wed' => 'W',
            'Thu' => 'Th',
            'Fri' => 'F',
            'Sat' => 'S',
            'Sun' => 'Su',
        ];

        $currentDay = $dayMap[$today];

        $facultyRecord = $faculty->faculty;

        $todaySchedules = collect();

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
            'nextClass'
        ));
    }
}
