<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Degree programs (BSIT, BSED, BSBIo, ...) that subjects are grouped under,
 * each belonging to a college.
 */
class ProgramController extends Controller
{
    public function index(): View
    {
        $programsByCollege = Program::query()
            ->with('college')
            ->withCount('subjects')
            ->inListOrder()
            ->get()
            ->groupBy(fn (Program $program) => $program->college_id ?? 0);

        $colleges = College::orderBy('id')->get()->keyBy('id');

        return view('admin.programs.index', compact('programsByCollege', 'colleges'));
    }

    public function create(Request $request): View
    {
        return view('admin.programs.create', [
            ...$this->formOptions(),
            'selectedCollegeId' => $request->integer('college') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Program::create($this->validated($request));

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program created successfully.');
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.edit', [
            ...$this->formOptions(),
            'program' => $program,
            'selectedCollegeId' => $program->college_id,
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $program->update($this->validated($request, $program));

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->subjects()->exists()) {
            return redirect()
                ->route('programs.index')
                ->with('error', 'This program cannot be deleted because subjects are still assigned to it. Remove it from those subjects first.');
        }

        $program->delete();

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program deleted successfully.');
    }

    /**
     * @return array{colleges: \Illuminate\Database\Eloquent\Collection<int, College>, groups: Collection<int, string>}
     */
    private function formOptions(): array
    {
        return [
            'colleges' => College::orderBy('id')->get(),
            'groups' => Program::whereNotNull('program_group')->distinct()->orderBy('program_group')->pluck('program_group'),
        ];
    }

    /**
     * Program codes keep their official spelling (BAComm, BSEd) but must be
     * unique regardless of capitals.
     *
     * @return array{college_id: ?int, program_code: string, program_name: string, program_group: ?string}
     */
    private function validated(Request $request, ?Program $program = null): array
    {
        $request->merge([
            'program_code' => trim((string) $request->input('program_code')),
            'program_group' => trim((string) $request->input('program_group')) ?: null,
        ]);

        return $request->validate([
            'college_id' => ['nullable', 'integer', 'exists:colleges,id'],
            'program_code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                function (string $attribute, mixed $value, Closure $fail) use ($program): void {
                    $taken = Program::query()
                        ->whereRaw('LOWER(program_code) = ?', [mb_strtolower((string) $value)])
                        ->when($program, fn ($query) => $query->whereKeyNot($program->id))
                        ->exists();

                    if ($taken) {
                        $fail('A program with this code already exists.');
                    }
                },
            ],
            'program_name' => ['required', 'string', 'max:150'],
            'program_group' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
