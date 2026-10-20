<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Degree programs (BSIT, BSED, BSBIO, ...) that subjects are grouped under.
 */
class ProgramController extends Controller
{
    public function index(): View
    {
        $programs = Program::withCount('subjects')
            ->orderBy('program_code')
            ->get();

        return view('admin.programs.index', compact('programs'));
    }

    public function create(): View
    {
        return view('admin.programs.create');
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
        return view('admin.programs.edit', compact('program'));
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
     * @return array{program_code: string, program_name: string}
     */
    private function validated(Request $request, ?Program $program = null): array
    {
        $request->merge(['program_code' => strtoupper(trim((string) $request->input('program_code')))]);

        return $request->validate([
            'program_code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('programs', 'program_code')->ignore($program)],
            'program_name' => ['required', 'string', 'max:150'],
        ]);
    }
}
