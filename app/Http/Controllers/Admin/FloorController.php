<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Models\Building;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $floors = Floor::with('building.campus')
            ->withCount('rooms')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('floor_number', 'like', "%{$search}%")
                        ->orWhereHas('building', function ($buildingQuery) use ($search) {
                            $buildingQuery
                                ->where('building_name', 'like', "%{$search}%")
                                ->orWhere('building_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->paginate(10)
            ->withQueryString();

        return view('admin.floors.index', compact(
            'floors',
            'search'
        ));
    }

    public function create()
    {
        $buildings = Building::with('campus')
            ->orderBy('building_name')
            ->get();

        return view('admin.floors.create', compact('buildings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        Floor::create($validated);

        return redirect()
            ->route('floors.index')
            ->with('success', 'Floor created successfully.');
    }

    public function show(Floor $floor)
    {
        $floor->load([
            'building.campus',
            'rooms',
        ]);

        return view('admin.floors.show', compact('floor'));
    }

    public function edit(Floor $floor)
    {
        $buildings = Building::with('campus')
            ->orderBy('building_name')
            ->get();

        return view('admin.floors.edit', compact(
            'floor',
            'buildings'
        ));
    }

    public function update(Request $request, Floor $floor)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'floor_number' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $floor->update($validated);

        return redirect()
            ->route('floors.index')
            ->with('success', 'Floor updated successfully.');
    }

    public function destroy(Floor $floor)
    {
        if ($floor->rooms()->exists()) {
            return redirect()
                ->route('floors.index')
                ->with('error', 'This floor cannot be deleted because it still has rooms.');
        }

        $floor->delete();

        return redirect()
            ->route('floors.index')
            ->with('success', 'Floor deleted successfully.');
    }
}