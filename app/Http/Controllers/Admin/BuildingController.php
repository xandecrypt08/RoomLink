<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    /**
     * Display a listing of buildings.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $buildings = Building::with('campus')
            ->withCount('floors')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('building_name', 'like', "%{$search}%")
                        ->orWhere('building_code', 'like', "%{$search}%")
                        ->orWhereHas('campus', function ($campus) use ($search) {
                            $campus->where(
                                'campus_name',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })
            ->orderBy('building_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.buildings.index', compact(
            'buildings',
            'search'
        ));
    }

    /**
     * Show the form for creating a new building.
     */
    public function create()
    {
        $campuses = Campus::orderBy('campus_name')->get();

        return view('admin.buildings.create', compact('campuses'));
    }

    /**
     * Store a newly created building.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'building_name' => 'required|string|max:255',
            'building_code' => 'nullable|string|max:50',
            'number_of_floors' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        Building::create($validated);

        return redirect()
            ->route('buildings.index')
            ->with('success', 'Building created successfully.');
    }

    /**
     * Display the specified building.
     */
    public function show(Building $building)
    {
        $building->load([
            'campus',
            'floors',
        ]);

        return view('admin.buildings.show', compact('building'));
    }

    /**
     * Show the form for editing the specified building.
     */
    public function edit(Building $building)
    {
        $campuses = Campus::orderBy('campus_name')->get();

        return view('admin.buildings.edit', compact(
            'building',
            'campuses'
        ));
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
            'number_of_floors' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $building->update($validated);

        return redirect()
            ->route('buildings.index')
            ->with('success', 'Building updated successfully.');
    }

    /**
     * Remove the specified building.
     */
    public function destroy(Building $building)
    {
        $building->delete();

        return redirect()
            ->route('buildings.index')
            ->with('success', 'Building deleted successfully.');
    }
}