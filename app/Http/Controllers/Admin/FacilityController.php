<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $facilities = Facility::withCount('rooms')
            ->when($search, function ($query) use ($search) {
                $query->where(
                    'facility_name',
                    'like',
                    "%{$search}%"
                );
            })
            ->orderBy('facility_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.facilities.index', compact(
            'facilities',
            'search'
        ));
    }

    public function create()
    {
        return view('admin.facilities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'facility_name' => 'required|string|max:100|unique:facilities,facility_name',
        ]);

        Facility::create($validated);

        return redirect()
            ->route('facilities.index')
            ->with('success', 'Facility created successfully.');
    }

    public function show(Facility $facility)
    {
        $facility->load([
            'rooms.floor.building.campus',
        ]);

        return view('admin.facilities.show', compact('facility'));
    }

    public function edit(Facility $facility)
    {
        return view('admin.facilities.edit', compact('facility'));
    }

    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'facility_name' => [
                'required',
                'string',
                'max:100',
                'unique:facilities,facility_name,' . $facility->id,
            ],
        ]);

        $facility->update($validated);

        return redirect()
            ->route('facilities.index')
            ->with('success', 'Facility updated successfully.');
    }

    public function destroy(Facility $facility)
    {
        $facility->rooms()->detach();

        $facility->delete();

        return redirect()
            ->route('facilities.index')
            ->with('success', 'Facility deleted successfully.');
    }
}