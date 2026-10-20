<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Colleges (COE, CAS, CME, ...) that degree programs belong to.
 */
class CollegeController extends Controller
{
    public function index(): View
    {
        $colleges = College::withCount('programs')
            ->orderBy('id')
            ->get();

        return view('admin.colleges.index', compact('colleges'));
    }

    public function create(): View
    {
        return view('admin.colleges.create');
    }

    public function store(Request $request): RedirectResponse
    {
        College::create($this->validated($request));

        return redirect()
            ->route('colleges.index')
            ->with('success', 'College created successfully.');
    }

    public function edit(College $college): View
    {
        return view('admin.colleges.edit', compact('college'));
    }

    public function update(Request $request, College $college): RedirectResponse
    {
        $college->update($this->validated($request, $college));

        return redirect()
            ->route('colleges.index')
            ->with('success', 'College updated successfully.');
    }

    public function destroy(College $college): RedirectResponse
    {
        if ($college->programs()->exists()) {
            return redirect()
                ->route('colleges.index')
                ->with('error', 'This college cannot be deleted because it still has programs. Move or delete its programs first.');
        }

        $college->delete();

        return redirect()
            ->route('colleges.index')
            ->with('success', 'College deleted successfully.');
    }

    /**
     * @return array{college_code: string, college_name: string}
     */
    private function validated(Request $request, ?College $college = null): array
    {
        $request->merge(['college_code' => trim((string) $request->input('college_code'))]);

        return $request->validate([
            'college_code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                function (string $attribute, mixed $value, Closure $fail) use ($college): void {
                    $taken = College::query()
                        ->whereRaw('LOWER(college_code) = ?', [mb_strtolower((string) $value)])
                        ->when($college, fn ($query) => $query->whereKeyNot($college->id))
                        ->exists();

                    if ($taken) {
                        $fail('A college with this code already exists.');
                    }
                },
            ],
            'college_name' => ['required', 'string', 'max:150'],
        ]);
    }
}
