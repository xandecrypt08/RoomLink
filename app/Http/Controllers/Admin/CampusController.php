<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    /**
     * Display a listing of campuses.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $campuses = Campus::withCount('buildings')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('campus_name', 'like', "%{$search}%")
                        ->orWhere('campus_code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('campus_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.campuses.index', compact(
            'campuses',
            'search'
        ));
    }

    /**
     * Show the form for creating a new campus.
     */
    public function create()
    {
        return view('admin.campuses.create');
    }

    /**
     * Store a newly created campus.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255',
            'campus_code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Campus::create($validated);

        return redirect()
            ->route('campuses.index')
            ->with('success', 'Campus created successfully.');
    }

    /**
     * Display the specified campus.
     */
    public function show(Campus $campus)
    {
        $campus->load('buildings');

        return view('admin.campuses.show', compact('campus'));
    }

    /**
     * Show the form for editing the specified campus.
     */
    public function edit(Campus $campus)
    {
        return view('admin.campuses.edit', compact('campus'));
    }

    /**
     * Update the specified campus.
     */
    public function update(Request $request, Campus $campus)
    {
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255',
            'campus_code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $campus->update($validated);

        return redirect()
            ->route('campuses.index')
            ->with('success', 'Campus updated successfully.');
    }

    /**
     * Remove the specified campus.
     */
    public function destroy(Campus $campus)
    {
        $campus->delete();

        return redirect()
            ->route('campuses.index')
            ->with('success', 'Campus deleted successfully.');
    }
}