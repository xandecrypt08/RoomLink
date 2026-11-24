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

        return view('admin.curriculum.index', [
            'colleges' => College::orderBy('id')->get(),
            'programs' => $programs,
            'programsByCollege' => $programs->groupBy(fn (Program $program) => $program->college_id ?? 0),
            'groups' => $programs->pluck('program_group')->filter()->unique()->sort()->values(),
            'subjects' => Subject::with('programs')
                ->withCount(['classSessions' => fn ($query) => $query->inTerm(SelectedTerm::get())])
                ->orderBy('subject_code')
                ->get(),
            'programId' => $request->integer('program') ?: null,
            'openImport' => $request->boolean('import'),
        ]);
    }
}
