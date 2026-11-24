<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Support\SelectedTerm;
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

        $classes = $faculty === null ? collect() : $faculty->classSessions()
            ->with(['room.floor.building', 'subject', 'section'])
            ->inTerm(SelectedTerm::get())
            ->where('status', 'active')
            ->orderedByWeek()
            ->get();

        return view('faculty.schedule.index', [
            'classes' => $classes,
            'classesByDay' => $classes->groupBy('day'),
            'dayNames' => ClassSession::DAY_NAMES,
        ]);
    }
}
