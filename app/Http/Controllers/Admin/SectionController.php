<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:100|unique:sections,section_name',
            'description' => 'nullable|string|max:500',
        ]);

        Section::create($validated);

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Section created successfully.');
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'section_name' => [
                'required',
                'string',
                'max:100',
                'unique:sections,section_name,'.$section->id,
            ],
            'description' => 'nullable|string|max:500',
        ]);

        $section->update($validated);

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        if ($section->classSessions()->exists()) {
            return redirect()
                ->route('schedules.index')
                ->with(
                    'error',
                    'This section cannot be deleted because it has class sessions.'
                );
        }

        $section->delete();

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Section deleted successfully.');
    }
}
