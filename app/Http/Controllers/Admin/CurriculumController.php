<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use App\Models\Subject;
use App\Support\SelectedTerm;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Colleges, their programs and the subjects on one page. Adding, editing and
 * deleting are handled by the controller of each record, which return here.
 */
class CurriculumController extends Controller
{
    public function index(Request $request): View
    {
        $programs = Program::with('college')->withCount('subjects')->inListOrder()->get();

        $subjects = Subject::with(['programs' => fn ($query) => $query->inListOrder()])
            ->withCount(['classSessions' => fn ($query) => $query->inTerm(SelectedTerm::get())])
            ->orderBy('subject_code')
            ->get();

        // The full list, program by program. A shared subject is under each of its programs.
        $subjectGroups = $programs->map(fn (Program $program): array => [
            'program' => $program,
            'subjects' => $subjects->filter(fn (Subject $subject) => $subject->programs->contains($program))->values(),
        ]);

        $withoutProgram = $subjects->filter(fn (Subject $subject) => $subject->programs->isEmpty())->values();

        if ($withoutProgram->isNotEmpty()) {
            $subjectGroups->push(['program' => null, 'subjects' => $withoutProgram]);
        }

        return view('admin.curriculum.index', [
            'colleges' => College::orderBy('id')->get(),
            'programs' => $programs,
            'programsByCollege' => $programs->groupBy(fn (Program $program) => $program->college_id ?? 0),
            'groups' => $programs->pluck('program_group')->filter()->unique()->sort()->values(),
            'subjects' => $subjects,
            'subjectGroups' => $subjectGroups,
            'programId' => $request->integer('program') ?: null,
            'openImport' => $request->boolean('import'),
        ]);
    }
}
