<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    /**
     * Store a newly created campus.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255',
            'campus_code' => 'nullable|string|max:50|unique:campuses,campus_code',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $campus = Campus::create($validated);

        return redirect()
            ->route('classrooms.index')
            ->withFragment("campus-{$campus->id}")
            ->with('success', 'Campus created successfully.');
    }

    /**
     * Update the specified campus.
     */
    public function update(Request $request, Campus $campus)
    {
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255',
            'campus_code' => 'nullable|string|max:50|unique:campuses,campus_code,'.$campus->id,
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $campus->update($validated);

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Campus updated successfully.');
    }

    /**
     * Remove the specified campus.
     */
    public function destroy(Campus $campus)
    {
        // Deleting would also remove every building, floor and room under it.
        if ($campus->buildings()->exists()) {
            return redirect()
                ->route('classrooms.index')
                ->with('error', 'This campus cannot be deleted because it still has buildings. Delete or move its buildings first.');
        }

        $campus->delete();

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Campus deleted successfully.');
    }
}
