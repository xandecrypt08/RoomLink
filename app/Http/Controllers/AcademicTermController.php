<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Support\SelectedTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicTermController extends Controller
{
    /**
     * Switch to another semester's class sessions. A semester that hasn't
     * been used yet starts empty.
     */
    public function select(Request $request): RedirectResponse
    {
        $request->validate(['term' => ['required', 'regex:/^\d{4}-[1-3]$/']]);

        [$schoolYear, $semester] = array_map('intval', explode('-', $request->input('term')));

        abort_unless(abs($schoolYear - (int) now()->year) <= 10, 422);

        SelectedTerm::set(AcademicTerm::firstOrCreate(['school_year' => $schoolYear, 'semester' => $semester]));

        return back();
    }

    /**
     * Make a semester the one RoomLink runs on.
     */
    public function makeCurrent(AcademicTerm $academicTerm): RedirectResponse
    {
        $academicTerm->makeCurrent();
        SelectedTerm::set($academicTerm);

        return back()->with('success', "{$academicTerm->label} is now the current semester.");
    }
}
