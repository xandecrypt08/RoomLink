<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
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

        $validated = $this->normalizeTimes($validated);

        $conflicts = $this->findConflicts($validated);

        if ($conflicts !== []) {
            return back()
                ->withInput()
                ->withErrors($conflicts);
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

        $validated = $this->normalizeTimes($validated);

        $conflicts = $this->findConflicts($validated, $schedule->id);

        if ($conflicts !== []) {
            return back()
                ->withInput()
                ->withErrors($conflicts);
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

    /**
     * Store times as H:i:s so they compare correctly with the existing
     * values in every database.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeTimes(array $validated): array
    {
        $validated['start_time'] .= ':00';
        $validated['end_time'] .= ':00';

        return $validated;
    }

    /**
     * Find active schedules on the same day whose time overlaps the given
     * one and that share its room, faculty member or section.
     *
     * Inactive schedules never conflict, and a schedule touching another
     * (one ends at 10:00, the next starts at 10:00) is not an overlap.
     *
     * @param  array{room_id: int|string, faculty_id: int|string, section_id: int|string, day: string, start_time: string, end_time: string, status: string}  $schedule
     * @return array<string, string> Error messages keyed by form field.
     */
    private function findConflicts(array $schedule, ?int $ignoreId = null): array
    {
        if ($schedule['status'] !== 'active') {
            return [];
        }

        $overlapping = ClassSession::with(['room', 'faculty', 'subject', 'section'])
            ->where('day', $schedule['day'])
            ->where('status', 'active')
            ->where('start_time', '<', $schedule['end_time'])
            ->where('end_time', '>', $schedule['start_time'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where(function ($query) use ($schedule) {
                $query->where('room_id', $schedule['room_id'])
                    ->orWhere('faculty_id', $schedule['faculty_id'])
                    ->orWhere('section_id', $schedule['section_id']);
            })
            ->orderBy('start_time')
            ->get();

        $messages = [
            'room_id' => 'This room is already booked',
            'faculty_id' => 'This faculty member is already teaching',
            'section_id' => 'This section already has a class',
        ];

        $errors = [];

        foreach ($messages as $field => $message) {
            $clash = $overlapping->first(fn (ClassSession $existing) => (int) $existing->{$field} === (int) $schedule[$field]);

            if ($clash !== null) {
                $errors[$field] = $message.' at that time: '.$this->describe($clash).'.';
            }
        }

        return $errors;
    }

    /**
     * Short description of a schedule for error messages,
     * e.g. "IT 101 (BSIT-1A) with Juan Dela Cruz in Room 101, 08:00–10:00".
     */
    private function describe(ClassSession $schedule): string
    {
        return sprintf(
            '%s (%s) with %s in %s, %s–%s',
            $schedule->subject->subject_code,
            $schedule->section->section_name,
            $schedule->faculty->full_name,
            $schedule->room->room_name,
            $schedule->start_time->format('H:i'),
            $schedule->end_time->format('H:i'),
        );
    }
}
