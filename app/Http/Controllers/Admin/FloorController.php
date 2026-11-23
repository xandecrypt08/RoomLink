<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|integer|min:0|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        Floor::create($validated);

        return redirect()
            ->route('classrooms.index')
            ->withFragment("building-{$validated['building_id']}")
            ->with('success', 'Floor created successfully.');
    }

    public function update(Request $request, Floor $floor)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|integer|min:0|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $floor->update($validated);

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Floor updated successfully.');
    }

    public function destroy(Floor $floor)
    {
        if ($floor->rooms()->exists()) {
            return redirect()
                ->route('classrooms.index')
                ->with('error', 'This floor cannot be deleted because it still has rooms.');
        }

        $floor->delete();

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Floor deleted successfully.');
    }
}
