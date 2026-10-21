<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Support\ScheduleImport\TeachingLoadParser;
use App\Support\ScheduleImport\TeachingLoadReview;
use App\Support\SubjectImport\DocumentText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Adds a faculty member's classes from their teaching load (class list or
 * timetable): upload it, review the classes found, then save them.
 *
 * Faculty upload their own load from My Schedule, and every class is theirs.
 * Administrators can upload one for any faculty member from Class Sessions.
 * Nothing is saved until the review is confirmed.
 */
class TeachingLoadController extends Controller
{
    public function create(Request $request): View
    {
        return view('teaching-load.create', [
            'routes' => $this->routeNames($request),
            'isAdmin' => $request->user()->isAdmin(),
            'faculty' => $request->user()->faculty,
            'faculties' => $request->user()->isAdmin() ? Faculty::orderBy('last_name')->orderBy('first_name')->get() : collect(),
        ]);
    }

    /**
     * Read the uploaded load and show the classes found for review.
     */
    public function preview(Request $request, DocumentText $documentText, TeachingLoadParser $parser, TeachingLoadReview $review): View|RedirectResponse
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'max:'.DocumentText::MAX_KILOBYTES, 'mimes:'.implode(',', DocumentText::EXTENSIONS)],
            'faculty_id' => [Rule::requiredIf($request->user()->isAdmin()), 'nullable', 'integer', 'exists:faculties,id'],
            'mode' => ['required', Rule::in(['add', 'replace'])],
        ], [
            'document.mimes' => 'Upload a PDF, Excel (.xlsx or .csv), Word (.docx), text file, or a photo (JPG, PNG, WEBP).',
            'document.max' => 'The file must be '.intdiv(DocumentText::MAX_KILOBYTES, 1024).' MB or smaller.',
            'faculty_id.required' => 'Choose whose teaching load this is.',
        ]);

        $faculty = $this->facultyFor($request);

        if ($faculty === null) {
            return redirect()->route($this->routeNames($request)['create']);
        }

        // Scanned PDFs are read page by page with OCR, several seconds a page.
        set_time_limit(60 + 30 * DocumentText::MAX_PAGES);

        try {
            $extracted = $documentText->extract($request->file('document'));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['document' => $exception->getMessage()]);
        }

        $classes = $parser->parse($extracted->text, $extracted->usedOcr);

        if ($classes === []) {
            return back()->withInput()->withErrors([
                'document' => 'No classes were found in this file. Each class should be on its own row with the subject code, the section and the schedule, '
                    .'for example "AI21  IT-109  IT Elective I  10:30 am-12:30 pm S COMLAB2A".',
            ]);
        }

        return $this->reviewView($request, $faculty, $validated['mode'], $review->check($classes, $faculty, $validated['mode'] === 'replace'), [
            'fileName' => $request->file('document')->getClientOriginalName(),
            'usedOcr' => $extracted->usedOcr,
            'text' => $extracted->text,
        ]);
    }

    /**
     * Save the classes kept on the review screen. If any ticked class still
     * has a problem, the review is shown again and nothing is saved.
     */
    public function store(Request $request, TeachingLoadReview $review): View|RedirectResponse
    {
        $routes = $this->routeNames($request);

        $validator = validator($request->all(), [
            'faculty_id' => [Rule::requiredIf($request->user()->isAdmin()), 'nullable', 'integer', 'exists:faculties,id'],
            'mode' => ['required', Rule::in(['add', 'replace'])],
            'rows' => ['required', 'array'],
            'rows.*.import' => ['nullable', 'boolean'],
            'rows.*.subject_code' => ['required_with:rows.*.import', 'nullable', 'string', 'max:50'],
            'rows.*.title' => ['nullable', 'string', 'max:150'],
            'rows.*.section' => ['required_with:rows.*.import', 'nullable', 'string', 'max:100'],
            'rows.*.day' => ['required_with:rows.*.import', 'nullable', Rule::in(array_keys(ClassSession::DAY_NAMES))],
            'rows.*.start_time' => ['required_with:rows.*.import', 'nullable', 'date_format:H:i'],
            'rows.*.end_time' => ['required_with:rows.*.import', 'nullable', 'date_format:H:i'],
            'rows.*.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'rows.*.room' => ['nullable', 'string', 'max:100'],
        ], [
            'rows.*.subject_code.required_with' => 'Every ticked class needs a Course No.',
            'rows.*.section.required_with' => 'Every ticked class needs a section.',
            'rows.*.day.required_with' => 'Every ticked class needs a day.',
            'rows.*.start_time.required_with' => 'Every ticked class needs a start and end time.',
            'rows.*.end_time.required_with' => 'Every ticked class needs a start and end time.',
            'rows.*.start_time.date_format' => 'Enter times like 10:30 AM.',
            'rows.*.end_time.date_format' => 'Enter times like 10:30 AM.',
        ]);

        $faculty = $this->facultyFor($request);

        if ($faculty === null || ! is_array($request->input('rows')) || $validator->errors()->hasAny(['faculty_id', 'mode', 'rows'])) {
            return redirect()->route($routes['create'])->with('error', 'Please upload the teaching load again.');
        }

        $mode = (string) $request->input('mode');

        $rows = collect($request->input('rows'))->values()->map(fn ($row) => [
            'subject_code' => (string) ($row['subject_code'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'section' => (string) ($row['section'] ?? ''),
            'day' => (string) ($row['day'] ?? ''),
            'start_time' => (string) ($row['start_time'] ?? ''),
            'end_time' => (string) ($row['end_time'] ?? ''),
            'room' => (string) ($row['room'] ?? ''),
            'room_id' => $row['room_id'] ?? null,
            'import' => ! empty($row['import']),
        ])->all();

        $checked = $review->check($rows, $faculty, $mode === 'replace');

        if ($validator->fails()) {
            return $this->reviewView($request, $faculty, $mode, $checked)->withErrors($validator);
        }

        $selected = collect($checked)->where('import', true);

        if ($selected->isEmpty()) {
            return redirect()->route($routes['create'])->with('error', 'No classes were ticked, so nothing was saved.');
        }

        if ($selected->contains(fn (array $row) => ! $row['duplicate'] && $row['problems'] !== [])) {
            return $this->reviewView($request, $faculty, $mode, $checked)
                ->withErrors(['rows' => 'Some ticked classes still have problems (marked below). Fix them or untick them, then save again.']);
        }

        $counts = $review->save($checked, $faculty, $mode === 'replace');

        $owner = $request->user()->isAdmin() ? $faculty->full_name."'s" : 'your';
        $message = 'Added '.$counts['classes'].' '.Str::plural('class', $counts['classes'])." to {$owner} schedule";
        $extras = array_filter([
            $counts['subjects'] ? $counts['subjects'].' new '.Str::plural('subject', $counts['subjects']) : null,
            $counts['sections'] ? $counts['sections'].' new '.Str::plural('section', $counts['sections']) : null,
            $counts['skipped'] ? $counts['skipped'].' already there '.($counts['skipped'] === 1 ? 'was' : 'were').' skipped' : null,
        ]);

        return redirect()
            ->route($routes['back'], $request->user()->isAdmin() ? ['search' => $faculty->last_name] : [])
            ->with('success', $message.($extras ? ' ('.implode('; ', $extras).')' : '').'.');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{fileName?: string, usedOcr?: bool, text?: string}  $source
     */
    private function reviewView(Request $request, Faculty $faculty, string $mode, array $rows, array $source = []): View
    {
        return view('teaching-load.review', [
            'routes' => $this->routeNames($request),
            'isAdmin' => $request->user()->isAdmin(),
            'faculty' => $faculty,
            'mode' => $mode,
            'rows' => collect($rows),
            'rooms' => app(TeachingLoadReview::class)->rooms(),
            'fileName' => $source['fileName'] ?? 'your teaching load',
            'usedOcr' => $source['usedOcr'] ?? false,
            'text' => $source['text'] ?? '',
        ]);
    }

    /**
     * Whose classes these are: the signed-in faculty member, or for an
     * administrator the faculty member chosen on the upload form.
     */
    private function facultyFor(Request $request): ?Faculty
    {
        if ($request->user()->isAdmin()) {
            return Faculty::find($request->integer('faculty_id'));
        }

        return $request->user()->faculty;
    }

    /**
     * Administrators import from Class Sessions; faculty from My Schedule.
     *
     * @return array{create: string, preview: string, store: string, back: string}
     */
    private function routeNames(Request $request): array
    {
        $prefix = $request->user()->isAdmin() ? 'schedules.import' : 'faculty.schedule.import';

        return [
            'create' => "{$prefix}.create",
            'preview' => "{$prefix}.preview",
            'store' => "{$prefix}.store",
            'back' => $request->user()->isAdmin() ? 'schedules.index' : 'faculty.schedule.index',
        ];
    }
}
