<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $users = User::with('faculty')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_student_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($role ?? '', User::ROLES), fn ($query) => $query->where('role', $role))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact(
            'users',
            'search',
            'role'
        ));
    }

    public function create(): View
    {
        $faculties = $this->linkableFaculties();

        return view('admin.users.create', compact('faculties'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->accountRules(),
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create($validated);

            $this->syncFacultyLink($user, $validated['faculty_id'] ?? null);

            return $user;
        });

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->full_name} created successfully.");
    }

    public function edit(User $user): View
    {
        $user->load('faculty');

        $faculties = $this->linkableFaculties($user);

        return view('admin.users.edit', compact('user', 'faculties'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validator = validator($request->all(), $this->accountRules($user));

        $validator->after(function (Validator $validator) use ($request, $user): void {
            if ($user->is($request->user()) && $request->input('role') !== 'admin') {
                $validator->errors()->add('role', 'You cannot remove your own administrator role.');
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($user, $validated): void {
            $user->update($validated);

            $this->syncFacultyLink($user, $validated['faculty_id'] ?? null);
        });

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->full_name} updated successfully.");
    }

    /**
     * Set a new password chosen by the administrator.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => $validated['password']]);

        return redirect()
            ->route('users.edit', $user)
            ->with('success', "Password for {$user->full_name} has been reset.");
    }

    /**
     * Deactivate an active account, or reactivate a deactivated one.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'reactivated' : 'deactivated';

        return redirect()
            ->route('users.index')
            ->with('success', "Account for {$user->full_name} has been {$status}.");
    }

    /**
     * Validation rules shared by creating and editing an account.
     *
     * @return array<string, mixed>
     */
    private function accountRules(?User $user = null): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'employee_student_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'employee_student_id')->ignore($user),
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'department' => 'nullable|string|max:150',
            'faculty_id' => [
                'nullable',
                'exclude_unless:role,faculty',
                Rule::exists('faculties', 'id')->where(function ($query) use ($user) {
                    $query->whereNull('user_id');

                    if ($user !== null) {
                        $query->orWhere('user_id', $user->id);
                    }
                }),
            ],
        ];
    }

    /**
     * Faculty records not yet linked to an account, plus the one linked to this user.
     *
     * @return Collection<int, Faculty>
     */
    private function linkableFaculties(?User $user = null): Collection
    {
        return Faculty::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id');

                if ($user !== null) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Link the user to the chosen faculty record and unlink any other.
     * Query-builder updates are used so the Faculty saving hook does not
     * re-link a record by employee ID.
     */
    private function syncFacultyLink(User $user, int|string|null $facultyId): void
    {
        Faculty::query()
            ->where('user_id', $user->id)
            ->when($facultyId !== null, fn ($query) => $query->whereKeyNot($facultyId))
            ->update(['user_id' => null]);

        if ($user->role === 'faculty' && $facultyId !== null) {
            Faculty::query()->whereKey($facultyId)->update(['user_id' => $user->id]);
        }
    }
}
