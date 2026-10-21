<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $programId = $request->integer('program') ?: null;

        $subjects = Subject::withCount('classSessions')
            ->with('programs')
            ->when($programId, fn ($query) => $query->whereHas('programs', fn ($q) => $q->whereKey($programId)))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('subject_code', 'like', "%{$search}%")
                        ->orWhere('subject_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('subject_code')
            ->paginate(10)
            ->withQueryString();

        $programs = Program::with('college')->inListOrder()->get();

        return view('admin.subjects.index', compact(
            'subjects',
            'search',
            'programs',
            'programId'
        ));
    }

    public function create()
    {
        $programs = Program::with('college')->inListOrder()->get();

        return view('admin.subjects.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code' => 'required|string|max:50|unique:subjects,subject_code',
            'subject_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'programs' => 'nullable|array',
            'programs.*' => 'integer|exists:programs,id',
        ]);

        $subject = Subject::create($validated);
        $subject->programs()->sync($validated['programs'] ?? []);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        $subject->load([
            'programs',
            'classSessions' => fn ($query) => $query->orderedByWeek(),
            'classSessions.faculty',
            'classSessions.section',
            'classSessions.room.floor.building.campus',
        ]);

        return view('admin.subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $subject->load('programs');
        $programs = Program::with('college')->inListOrder()->get();

        return view('admin.subjects.edit', compact('subject', 'programs'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_code' => [
                'required',
                'string',
                'max:50',
                'unique:subjects,subject_code,'.$subject->id,
            ],
            'subject_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'programs' => 'nullable|array',
            'programs.*' => 'integer|exists:programs,id',
        ]);

        $subject->update($validated);
        $subject->programs()->sync($validated['programs'] ?? []);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->classSessions()->exists()) {
            return redirect()
                ->route('subjects.index')
                ->with(
                    'error',
                    'This subject cannot be deleted because it has class sessions.'
                );
        }

        $subject->delete();

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }
}
