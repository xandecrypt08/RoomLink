<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ProfilePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every signed-in user's own profile: photo, personal details and password.
 * Which details can be changed depends on the role (User::editableProfileFields);
 * anything else sent with the form is ignored.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'editable' => $request->user()->editableProfileFields(),
        ]);
    }

    public function update(Request $request, ProfilePhoto $profilePhoto): RedirectResponse
    {
        $user = $request->user();
        $editable = $user->editableProfileFields();
        $faculty = $user->isFaculty() ? $user->faculty : null;

        $rules = array_intersect_key([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'employee_student_id' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_student_id')->ignore($user)],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user),
                // The faculty record keeps its own copy of the email address.
                ...($faculty ? [Rule::unique('faculties', 'email')->ignore($faculty)] : []),
            ],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()\-]{5,}$/'],
            'department' => ['nullable', 'string', 'max:150'],
        ], array_flip($editable));

        $validated = $request->validate([
            ...$rules,
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'contact_number.regex' => 'Enter a phone number using digits, for example 0917 123 4567 or +63 917 123 4567.',
            'photo.max' => 'The photo must be 5 MB or smaller.',
            'photo.mimes' => 'Upload a JPG, PNG or WEBP photo.',
            'photo.image' => 'Upload a JPG, PNG or WEBP photo.',
        ]);

        $details = array_intersect_key($validated, array_flip($editable));
        $oldPhoto = null;

        if ($request->hasFile('photo')) {
            try {
                $details['profile_photo'] = $profilePhoto->store($request->file('photo'));
            } catch (RuntimeException $exception) {
                return back()->withInput()->withErrors(['photo' => $exception->getMessage()]);
            }

            $oldPhoto = $user->profile_photo;
        }

        DB::transaction(function () use ($user, $details, $faculty): void {
            $user->update($details);

            if ($faculty !== null && isset($details['email'])) {
                $faculty->update(['email' => $details['email']]);
            }
        });

        $profilePhoto->delete($oldPhoto);

        return redirect()->route('profile.edit')->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return redirect()->route('profile.edit')->with('success', 'Your password has been changed.');
    }

    public function destroyPhoto(Request $request, ProfilePhoto $profilePhoto): RedirectResponse
    {
        $user = $request->user();
        $oldPhoto = $user->profile_photo;

        $user->update(['profile_photo' => null]);
        $profilePhoto->delete($oldPhoto);

        return redirect()->route('profile.edit')->with('success', 'Your profile photo has been removed.');
    }

    /**
     * A user's profile photo, for any signed-in user (it appears next to
     * their name). Photos are kept outside the public folder.
     */
    public function photo(User $user): StreamedResponse
    {
        abort_if(! $user->profile_photo || ! Storage::disk('local')->exists($user->profile_photo), 404);

        return Storage::disk('local')->response($user->profile_photo, null, [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }
}
