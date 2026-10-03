<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Room;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Section;
use Illuminate\Http\Request;

class ClassSessionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $day = $request->input('day');
        $status = $request->input('status');

        $schedules = ClassSession::with([
            'room.floor.building.campus',
            'faculty',
            'subject',
            'section',
        ])
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->whereHas('room', function ($roomQuery) use ($search) {
                        $roomQuery->where(
                            'room_name',
                            'like',
                            "%{$search}%"
                        );
                    })

                    ->orWhereHas('faculty', function ($facultyQuery) use ($search) {
                        $facultyQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })

                    ->orWhereHas('subject', function ($subjectQuery) use ($search) {
                        $subjectQuery
                            ->where('subject_code', 'like', "%{$search}%")
                            ->orWhere('subject_name', 'like', "%{$search}%");
                    })

                    ->orWhereHas('section', function ($sectionQuery) use ($search) {
                        $sectionQuery->where(
                            'section_name',
                            'like',
                            "%{$search}%"
                        );
                    });

                });

            })

            ->when($day, function ($query) use ($day) {
                $query->where('day', $day);
            })

            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })

            ->orderByRaw("
                FIELD(
                    day,
                    'M',
                    'T',
                    'W',
                    'Th',
                    'F',
                    'S',
                    'Su'
                )
            ")

            ->orderBy('start_time')

            ->paginate(10)

            ->withQueryString();

        return view('admin.schedules.index', compact(
            'schedules',
            'search',
            'day',
            'status'
        ));
    }


    public function create()
    {
        $rooms = Room::with('floor.building.campus')
            ->orderBy('room_name')
            ->get();

        $faculties = Faculty::orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $subjects = Subject::orderBy('subject_code')
            ->get();

        $sections = Section::orderBy('section_name')
            ->get();

        return view('admin.schedules.create', compact(
            'rooms',
            'faculties',
            'subjects',
            'sections'
        ));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'faculty_id' => 'required|exists:faculties,id',
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'day' => 'required|in:M,T,W,Th,F,S,Su',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:500',
        ]);

        $conflict = false;

        if ($validated['status'] === 'active') {
            $conflict = $this->hasConflict(
                $validated['room_id'],
                $validated['day'],
                $validated['start_time'],
                $validated['end_time']
            );
        }

        if ($conflict) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_time' =>
                        'This room already has an active class session during the selected time.',
                ]);
        }

        ClassSession::create($validated);

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Class session created successfully.');
    }


    public function show(ClassSession $schedule)
    {
        $schedule->load([
            'room.floor.building.campus',
            'room.facilities',
            'faculty',
            'subject',
            'section',
        ]);

        return view('admin.schedules.show', compact(
            'schedule'
        ));
    }


    public function edit(ClassSession $schedule)
    {
        $rooms = Room::with('floor.building.campus')
            ->orderBy('room_name')
            ->get();

        $faculties = Faculty::orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $subjects = Subject::orderBy('subject_code')
            ->get();

        $sections = Section::orderBy('section_name')
            ->get();

        return view('admin.schedules.edit', compact(
            'schedule',
            'rooms',
            'faculties',
            'subjects',
            'sections'
        ));
    }


    public function update(Request $request, ClassSession $schedule)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'faculty_id' => 'required|exists:faculties,id',
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'day' => 'required|in:M,T,W,Th,F,S,Su',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:500',
        ]);

        $conflict = false;

        if ($validated['status'] === 'active') {
            $conflict = $this->hasConflict(
                $validated['room_id'],
                $validated['day'],
                $validated['start_time'],
                $validated['end_time'],
                $schedule->id
            );
        }

        if ($conflict) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_time' =>
                        'This room already has an active class session during the selected time.',
                ]);
        }

        $schedule->update($validated);

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Class session updated successfully.');
    }


    public function destroy(ClassSession $schedule)
    {
        $schedule->delete();

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Class session deleted successfully.');
    }


    private function hasConflict(
        int $roomId,
        string $day,
        string $startTime,
        string $endTime,
        ?int $ignoreId = null
    ): bool {

        return ClassSession::where('room_id', $roomId)
            ->where('day', $day)
            ->where('status', 'active')

            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })

            ->where(function ($query) use ($startTime, $endTime) {

                $query
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);

            })

            ->exists();
    }
}