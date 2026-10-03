<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $sections = Section::withCount('classSessions')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('section_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('section_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.sections.index', compact(
            'sections',
            'search'
        ));
    }

    public function create()
    {
        return view('admin.sections.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:100|unique:sections,section_name',
            'description' => 'nullable|string|max:500',
        ]);

        Section::create($validated);

        return redirect()
            ->route('sections.index')
            ->with('success', 'Section created successfully.');
    }

    public function show(Section $section)
    {
        $section->load([
            'classSessions.faculty',
            'classSessions.subject',
            'classSessions.room.floor.building.campus',
        ]);

        return view('admin.sections.show', compact('section'));
    }

    public function edit(Section $section)
    {
        return view('admin.sections.edit', compact('section'));
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'section_name' => [
                'required',
                'string',
                'max:100',
                'unique:sections,section_name,' . $section->id,
            ],
            'description' => 'nullable|string|max:500',
        ]);

        $section->update($validated);

        return redirect()
            ->route('sections.index')
            ->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        if ($section->classSessions()->exists()) {
            return redirect()
                ->route('sections.index')
                ->with(
                    'error',
                    'This section cannot be deleted because it has class sessions.'
                );
        }

        $section->delete();

        return redirect()
            ->route('sections.index')
            ->with('success', 'Section deleted successfully.');
    }
}