<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $subjects = Subject::withCount('classSessions')
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

        return view('admin.subjects.index', compact(
            'subjects',
            'search'
        ));
    }

    public function create()
    {
        return view('admin.subjects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code' => 'required|string|max:50|unique:subjects,subject_code',
            'subject_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        Subject::create($validated);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        $subject->load([
            'classSessions.faculty',
            'classSessions.section',
            'classSessions.room.floor.building.campus',
        ]);

        return view('admin.subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_code' => [
                'required',
                'string',
                'max:50',
                'unique:subjects,subject_code,' . $subject->id,
            ],
            'subject_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $subject->update($validated);

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