<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Support\ScheduleConflicts;
use App\Support\SelectedTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Class Sessions on one page, with its sections and the teaching-load import.
 *
 * One form saves a class on several days at once. The subject and section are
 * typed in and created when new, and a new faculty member can be added in the
 * same form.
 */
class ClassSessionController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.schedules.index', [
            'schedules' => ClassSession::with(['room.floor.building', 'faculty', 'subject', 'section'])
                ->inTerm(SelectedTerm::get())
                ->orderedByWeek()
                ->get(),
            'term' => SelectedTerm::get(),
            'sections' => Section::withCount(['classSessions' => fn ($query) => $query->inTerm(SelectedTerm::get())])->orderBy('section_name')->get(),
            'subjects' => Subject::orderBy('subject_code')->get(['id', 'subject_code', 'subject_name']),
            'faculties' => Faculty::orderBy('last_name')->orderBy('first_name')->get(),
            'rooms' => Room::with('floor.building')->orderBy('room_name')->get(),
            'search' => $request->input('search'),
            // Links from other pages open a form straight away.
            'openForm' => match (true) {
                $request->boolean('add') => 'add',
                $request->boolean('load') => 'load',
                default => null,
            },
            'editId' => $request->integer('edit') ?: null,
            'roomId' => $request->integer('room_id') ?: null,
            'facultyId' => $request->integer('faculty_id') ?: null,
        ]);
    }

    /**
     * Classes are added in a pop-up on the Class Sessions page.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('schedules.index', ['add' => 1, ...$request->only('room_id')]);
    }

    /**
     * Save the class on every ticked day, or on none if any day has a conflict.
     */
    public function store(Request $request): RedirectResponse
    {
        $term = SelectedTerm::fromForm($request);
        $class = [...$this->validatedClass($request), 'academic_term_id' => $term->id];

        $this->rejectConflicts($class);

        $saved = DB::transaction(function () use ($class): array {
            $details = $this->withRecords($class);

            foreach ($class['days'] as $day) {
                ClassSession::create([...$details, 'day' => $day, 'academic_term_id' => $class['academic_term_id']]);
            }

            return $details;
        });

        SelectedTerm::set($term);

        $count = count($class['days']);
        $redirect = redirect()
            ->route('schedules.index')
            ->with('success', $count === 1 ? 'Class session created successfully.' : "{$count} class sessions created successfully.");

        // Keep the details that usually stay the same for the next class.
        if ($request->boolean('another')) {
            $redirect->with('repeat', [
                '_dialog' => 'session',
                'faculty_id' => $saved['faculty_id'],
                'section_name' => Section::find($saved['section_id'])->section_name,
                'subject_code' => Subject::find($saved['subject_id'])->subject_code,
                'room_id' => $saved['room_id'],
            ]);
        }

        return $redirect;
    }

    /**
     * Conflicts of the class being filled in, shown while the form is open.
     * Works on a partly filled form; saving checks everything again.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'days' => ['nullable', 'array'],
            'days.*' => [Rule::in(array_keys(ClassSession::DAY_NAMES))],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'section_name' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $start = (string) $request->input('start_time');
        $end = (string) $request->input('end_time');
        $isTime = fn (string $time): bool => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) === 1;

        if (! $isTime($start) || ! $isTime($end)) {
            return response()->json(['conflicts' => (object) [], 'complete' => false]);
        }

        if ($end <= $start) {
            return response()->json(['conflicts' => ['end_time' => ['The class must end after it starts.']], 'complete' => false]);
        }

        $edited = ClassSession::find($request->integer('_record'));

        $class = [
            'academic_term_id' => $edited->academic_term_id ?? SelectedTerm::fromForm($request)->id,
            'room_id' => $request->integer('room_id'),
            'faculty_id' => $request->integer('faculty_id'),
            'section_id' => $this->sectionIdFor((string) $request->input('section_name')),
            'days' => array_values(array_unique($request->input('days') ?? [])),
            'start_time' => $start.':00',
            'end_time' => $end.':00',
            'status' => $request->input('status') ?? 'active',
        ];

        $conflicts = $this->conflicts($class, $edited?->id);

        $answer = [
            'conflicts' => (object) $conflicts,
            'complete' => $class['days'] !== [] && $class['room_id'] > 0
                && $request->filled('faculty_id') && $request->filled('section_name'),
        ];

        if (isset($conflicts['room_id'])) {
            $answer['free_rooms'] = app(ScheduleConflicts::class)
                ->freeRooms($class, $class['days'], $edited?->id)
                ->map(fn (Room $room): array => [
                    'id' => $room->id,
                    'label' => $room->room_name.($room->floor?->building ? ' · '.$room->floor->building->building_name : ''),
                ]);
        }

        return response()->json($answer);
    }

    /**
     * Classes are edited in a pop-up on the Class Sessions page.
     */
    public function edit(ClassSession $schedule): RedirectResponse
    {
        // The page lists one term, so show the class's own.
        if ($schedule->academicTerm !== null) {
            SelectedTerm::set($schedule->academicTerm);
        }

        return redirect()->route('schedules.index', ['edit' => $schedule->id]);
    }

    public function update(Request $request, ClassSession $schedule): RedirectResponse
    {
        $class = [...$this->validatedClass($request), 'academic_term_id' => $schedule->academic_term_id];

        if (count($class['days']) > 1) {
            throw ValidationException::withMessages(['days' => 'Choose one day when editing. Use Copy to add the class on another day.']);
        }

        $this->rejectConflicts($class, $schedule->id);

        DB::transaction(function () use ($class, $schedule): void {
            $schedule->update([...$this->withRecords($class), 'day' => $class['days'][0]]);
        });

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Class session updated successfully.');
    }

    public function destroy(ClassSession $schedule): RedirectResponse
    {
        // Keep held classes linked to their schedule in the session log.
        if ($schedule->roomSessions()->exists()) {
            return redirect()
                ->route('schedules.index')
                ->with('error', 'This class session cannot be deleted because it has session history. Set its status to Inactive instead.');
        }

        $schedule->delete();

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Class session deleted successfully.');
    }

    /**
     * The submitted class. Subject and section are given by ID or typed in;
     * a typed one that already exists is reused.
     *
     * @return array<string, mixed>
     */
    private function validatedClass(Request $request): array
    {
        if (! $request->has('days') && $request->filled('day')) {
            $request->merge(['days' => [$request->input('day')]]);
        }

        $newFaculty = $request->input('faculty_id') === 'new';

        $class = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'faculty_id' => ['required', $newFaculty ? 'in:new' : 'exists:faculties,id'],
            'faculty_employee_id' => [Rule::excludeIf(! $newFaculty), 'required', 'string', 'max:50', 'unique:faculties,employee_id'],
            'faculty_first_name' => [Rule::excludeIf(! $newFaculty), 'required', 'string', 'max:100'],
            'faculty_last_name' => [Rule::excludeIf(! $newFaculty), 'required', 'string', 'max:100'],
            'faculty_email' => [Rule::excludeIf(! $newFaculty), 'nullable', 'email', 'max:150', 'unique:faculties,email'],
            'subject_id' => ['required_without:subject_code', 'nullable', 'exists:subjects,id'],
            'subject_code' => ['required_without:subject_id', 'nullable', 'string', 'max:50'],
            'subject_name' => ['nullable', 'string', 'max:150'],
            'section_id' => ['required_without:section_name', 'nullable', 'exists:sections,id'],
            'section_name' => ['required_without:section_id', 'nullable', 'string', 'max:100'],
            'days' => ['required', 'array'],
            'days.*' => ['distinct', Rule::in(array_keys(ClassSession::DAY_NAMES))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'days.required' => 'Choose at least one day.',
            'subject_code.required_without' => 'Enter the course number.',
            'section_name.required_without' => 'Enter the section.',
        ], [
            'room_id' => 'room',
            'faculty_id' => 'faculty member',
            'faculty_employee_id' => 'employee number',
            'faculty_first_name' => 'first name',
            'faculty_last_name' => 'last name',
            'faculty_email' => 'email',
        ]);

        $class['days'] = array_values($class['days']);

        // Stored as H:i:s so times compare correctly in every database.
        $class['start_time'] .= ':00';
        $class['end_time'] .= ':00';

        if (empty($class['subject_id'])) {
            $code = Subject::normalizeCode($class['subject_code']);

            $class['subject_code'] = $code;
            $class['subject_id'] = Subject::all(['id', 'subject_code'])
                ->first(fn (Subject $subject) => Subject::normalizeCode($subject->subject_code) === $code)?->id;

            if ($class['subject_id'] === null && blank($class['subject_name'] ?? null)) {
                throw ValidationException::withMessages(['subject_name' => "{$code} is a new subject. Enter its descriptive title."]);
            }
        }

        if (empty($class['section_id'])) {
            $class['section_id'] = $this->sectionIdFor($class['section_name']);
        }

        return $class;
    }

    private function sectionIdFor(string $name): ?int
    {
        return Section::query()
            ->whereRaw('LOWER(section_name) = ?', [mb_strtolower(trim($name))])
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $class
     */
    private function rejectConflicts(array $class, ?int $ignoreId = null): void
    {
        $conflicts = $this->conflicts($class, $ignoreId);

        if ($conflicts !== []) {
            throw ValidationException::withMessages($conflicts);
        }
    }

    /**
     * Conflicts on any of the class's days, by field. A room, faculty member
     * or section that is new or not chosen yet can't be in another class.
     *
     * @param  array<string, mixed>  $class
     * @return array<string, list<string>>
     */
    private function conflicts(array $class, ?int $ignoreId = null): array
    {
        $messages = [];

        foreach ($class['days'] as $day) {
            $found = app(ScheduleConflicts::class)->find([
                ...$class,
                'day' => $day,
                'room_id' => (int) $class['room_id'],
                'faculty_id' => (int) $class['faculty_id'],
                'section_id' => (int) $class['section_id'],
            ], $ignoreId);

            foreach ($found as $field => $message) {
                $messages[$field][] = count($class['days']) > 1 ? ClassSession::DAY_NAMES[$day].': '.$message : $message;
            }
        }

        return $messages;
    }

    /**
     * The class's own columns, after creating its new subject, section or
     * faculty member.
     *
     * @param  array<string, mixed>  $class
     * @return array<string, mixed>
     */
    private function withRecords(array $class): array
    {
        $facultyId = $class['faculty_id'] !== 'new' ? (int) $class['faculty_id'] : Faculty::create([
            'employee_id' => $class['faculty_employee_id'],
            'first_name' => $class['faculty_first_name'],
            'last_name' => $class['faculty_last_name'],
            'email' => $class['faculty_email'] ?? null,
        ])->id;

        $subjectId = $class['subject_id'] ?? Subject::create([
            'subject_code' => $class['subject_code'],
            'subject_name' => trim($class['subject_name']),
        ])->id;

        $sectionId = $class['section_id'] ?? Section::create(['section_name' => trim($class['section_name'])])->id;

        return [
            'room_id' => (int) $class['room_id'],
            'faculty_id' => $facultyId,
            'subject_id' => (int) $subjectId,
            'section_id' => (int) $sectionId,
            'start_time' => $class['start_time'],
            'end_time' => $class['end_time'],
            'status' => $class['status'],
            'description' => $class['description'] ?? null,
        ];
    }
}
