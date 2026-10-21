<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in faculty member's weekly classes, where they upload their
 * teaching load.
 */
class MyScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $faculty = $request->user()->faculty;

        $classesByDay = $faculty === null ? collect() : $faculty->classSessions()
            ->with(['room.floor.building', 'subject', 'section'])
            ->where('status', 'active')
            ->orderedByWeek()
            ->get()
            ->groupBy('day');

        return view('faculty.schedule.index', [
            'faculty' => $faculty,
            'classesByDay' => $classesByDay,
            'dayNames' => ClassSession::DAY_NAMES,
        ]);
    }
}
