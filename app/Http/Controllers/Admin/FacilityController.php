<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'facility_name' => 'required|string|max:100|unique:facilities,facility_name',
        ]);

        Facility::create($validated);

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Facility created successfully.');
    }

    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'facility_name' => [
                'required',
                'string',
                'max:100',
                'unique:facilities,facility_name,'.$facility->id,
            ],
        ]);

        $facility->update($validated);

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Facility updated successfully.');
    }

    public function destroy(Facility $facility)
    {
        $facility->rooms()->detach();

        $facility->delete();

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Facility deleted successfully.');
    }
}
