<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuildingController extends Controller
{
    /**
     * Store a newly created building.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'building_name' => 'required|string|max:255',
            'building_code' => 'nullable|string|max:50',
            'number_of_floors' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        $building = DB::transaction(function () use ($validated): Building {
            $building = Building::create($validated);
            $building->createMissingFloors();

            return $building;
        });

        return redirect()
            ->route('classrooms.index')
            ->withFragment("building-{$building->id}")
            ->with('success', "{$building->building_name} created with {$building->number_of_floors} ".Str::plural('floor', (int) $building->number_of_floors).'.');
    }

    /**
     * Create the floors a building should have but doesn't yet.
     */
    public function createFloors(Building $building): RedirectResponse
    {
        $created = $building->createMissingFloors();

        return redirect()
            ->route('classrooms.index')
            ->withFragment("building-{$building->id}")
            ->with('success', "Added {$created} ".Str::plural('floor', $created)." to {$building->building_name}.");
    }

    /**
     * Update the specified building.
     */
    public function update(
        Request $request,
        Building $building
    ) {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'building_name' => 'required|string|max:255',
            'building_code' => 'nullable|string|max:50',
            'number_of_floors' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        $building->update($validated);

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Building updated successfully.');
    }

    /**
     * Remove the specified building.
     */
    public function destroy(Building $building)
    {
        // Deleting would also remove every floor and room in it.
        if ($building->floors()->exists()) {
            return redirect()
                ->route('classrooms.index')
                ->with('error', 'This building cannot be deleted because it still has floors. Delete its floors first.');
        }

        $building->delete();

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Building deleted successfully.');
    }
}
