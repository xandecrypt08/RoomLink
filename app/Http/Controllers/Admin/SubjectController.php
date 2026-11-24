<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
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
            ->route('curriculum.index')
            ->with('success', 'Subject created successfully.');
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
            ->route('curriculum.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->classSessions()->exists()) {
            return redirect()
                ->route('curriculum.index')
                ->with(
                    'error',
                    'This subject cannot be deleted because it has class sessions.'
                );
        }

        $subject->delete();

        return redirect()
            ->route('curriculum.index')
            ->with('success', 'Subject deleted successfully.');
    }
}
