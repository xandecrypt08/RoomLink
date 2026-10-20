<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Subject;
use App\Support\SubjectImport\DocumentText;
use App\Support\SubjectImport\SubjectListParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

/**
 * Adds subjects in bulk from a curriculum or prospectus: upload the document,
 * review the Course Nos. and Descriptive Titles found in it, then import.
 *
 * Nothing is saved until the review is confirmed. Codes that already exist
 * are not duplicated; the chosen programs are added to them instead.
 */
class SubjectImportController extends Controller
{
    public function create(): View
    {
        $programs = Program::orderBy('program_code')->get();

        return view('admin.subjects.import', compact('programs'));
    }

    /**
     * Read the uploaded document and show what was found for review.
     */
    public function preview(Request $request, DocumentText $documentText, SubjectListParser $parser): View|RedirectResponse
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:'.implode(',', DocumentText::EXTENSIONS)],
            'programs' => ['required', 'array', 'min:1'],
            'programs.*' => ['integer', 'exists:programs,id'],
        ], [
            'document.mimes' => 'Upload a PDF, Word (.docx), text file, or a photo (JPG, PNG, WEBP).',
            'document.max' => 'The file must be 10 MB or smaller.',
            'programs.required' => 'Choose at least one program for these subjects.',
        ]);

        try {
            $text = $documentText->extract($request->file('document'));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['document' => $exception->getMessage()]);
        }

        $found = $parser->parse($text, DocumentText::isImage($request->file('document')));

        if ($found === []) {
            return back()->withInput()->withErrors([
                'document' => 'No course codes were found in this document. Subjects should be listed one per row, starting with the Course No. (for example "IT-110 Introduction to Computing").',
            ]);
        }

        $existing = $this->existingSubjectsByCode();

        $rows = collect($found)->map(fn (array $row) => [
            ...$row,
            'existing' => $existing->get($row['code']),
        ]);

        $programs = Program::whereKey($validated['programs'])->orderBy('program_code')->get();

        return view('admin.subjects.import-preview', [
            'rows' => $rows,
            'programs' => $programs,
            'fileName' => $request->file('document')->getClientOriginalName(),
            'text' => $text,
        ]);
    }

    /**
     * Save the subjects the administrator kept on the review screen.
     */
    public function store(Request $request): View|RedirectResponse
    {
        $validator = validator($request->all(), [
            'programs' => ['required', 'array', 'min:1'],
            'programs.*' => ['integer', 'exists:programs,id'],
            'rows' => ['required', 'array'],
            'rows.*.import' => ['nullable', 'boolean'],
            'rows.*.code' => ['required_with:rows.*.import', 'nullable', 'string', 'max:50'],
            'rows.*.title' => ['required_with:rows.*.import', 'nullable', 'string', 'max:150'],
        ], [
            'rows.*.code.required_with' => 'Every selected row needs a Course No.',
            'rows.*.title.required_with' => 'Every selected row needs a Descriptive Title.',
        ]);

        // The review page only exists as the result of an upload, so show it
        // again with the administrator's edits rather than redirecting back.
        if ($validator->fails()) {
            if (! is_array($request->input('rows')) || ! is_array($request->input('programs'))) {
                return redirect()->route('subjects.import.create')->with('error', 'Please upload the document again.');
            }

            return $this->reviewAgain($request)->withErrors($validator);
        }

        $validated = $validator->validated();

        $selected = collect($validated['rows'])->filter(fn (array $row) => ! empty($row['import']));

        if ($selected->isEmpty()) {
            return redirect()
                ->route('subjects.import.create')
                ->with('error', 'No subjects were selected, so nothing was imported.');
        }

        [$created, $updated] = DB::transaction(function () use ($selected, $validated): array {
            $existing = $this->existingSubjectsByCode();
            $created = 0;
            $updated = 0;

            foreach ($selected as $row) {
                $code = Subject::normalizeCode($row['code']);
                $subject = $existing->get($code);

                if ($subject === null) {
                    $subject = Subject::create([
                        'subject_code' => $code,
                        'subject_name' => trim($row['title']),
                    ]);

                    $existing->put($code, $subject);
                    $created++;
                } else {
                    $updated++;
                }

                $subject->programs()->syncWithoutDetaching($validated['programs']);
            }

            return [$created, $updated];
        });

        $programCodes = Program::whereKey($validated['programs'])->orderBy('program_code')->pluck('program_code')->implode(', ');

        return redirect()
            ->route('subjects.index', count($validated['programs']) === 1 ? ['program' => $validated['programs'][0]] : [])
            ->with('success', "Imported {$created} new subject".($created === 1 ? '' : 's')." for {$programCodes}"
                .($updated ? "; added {$programCodes} to {$updated} existing subject".($updated === 1 ? '' : 's') : '').'.');
    }

    /**
     * The review screen rebuilt from the submitted rows, keeping the edits.
     */
    private function reviewAgain(Request $request): View
    {
        $existing = $this->existingSubjectsByCode();

        $rows = collect($request->input('rows'))->values()->map(fn ($row) => [
            'code' => (string) ($row['code'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'import' => ! empty($row['import']),
            'existing' => $existing->get(Subject::normalizeCode((string) ($row['code'] ?? ''))),
        ]);

        return view('admin.subjects.import-preview', [
            'rows' => $rows,
            'programs' => Program::whereKey($request->input('programs'))->orderBy('program_code')->get(),
            'fileName' => 'your document',
            'text' => '',
        ]);
    }

    /**
     * Existing subjects keyed by their normalised code, so "IT 110" in the
     * database matches "IT-110" in a document.
     *
     * @return Collection<string, Subject>
     */
    private function existingSubjectsByCode(): Collection
    {
        return Subject::all()->keyBy(fn (Subject $subject) => Subject::normalizeCode($subject->subject_code));
    }
}
