<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $faculties = Faculty::withCount('classSessions')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('employee_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.faculties.index', compact(
            'faculties',
            'search'
        ));
    }

    public function create()
    {
        return view('admin.faculties.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|max:50|unique:faculties,employee_id',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150|unique:faculties,email',
            'department' => 'nullable|string|max:150',
        ]);

        Faculty::create($validated);

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Faculty member created successfully.');
    }

    public function show(Faculty $faculty)
    {
        $faculty->load([
            'classSessions.room.floor.building.campus',
            'classSessions.subject',
            'classSessions.section',
        ]);

        return view('admin.faculties.show', compact('faculty'));
    }

    public function edit(Faculty $faculty)
    {
        return view('admin.faculties.edit', compact('faculty'));
    }

    public function update(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                'string',
                'max:50',
                'unique:faculties,employee_id,'.$faculty->id,
            ],
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'nullable',
                'email',
                'max:150',
                'unique:faculties,email,'.$faculty->id,
            ],
            'department' => 'nullable|string|max:150',
        ]);

        $faculty->update($validated);

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Faculty member updated successfully.');
    }

    public function destroy(Faculty $faculty)
    {
        if ($faculty->classSessions()->exists() || $faculty->roomSessions()->exists()) {
            return redirect()
                ->route('faculties.index')
                ->with(
                    'error',
                    'This faculty member cannot be deleted because they have class schedules or session history.'
                );
        }

        $faculty->delete();

        return redirect()
            ->route('faculties.index')
            ->with('success', 'Faculty member deleted successfully.');
    }
}
