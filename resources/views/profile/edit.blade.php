@extends('layouts.app')

@section('title', 'My Profile')

@section('content')

@php
    $initials = strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1));

    $idLabel = match ($user->role) {
        'student' => 'Student Number',
        default => 'Employee Number',
    };

    $fields = [
        'first_name' => ['label' => 'First Name', 'type' => 'text', 'required' => true, 'autocomplete' => 'given-name'],
        'middle_name' => ['label' => 'Middle Name', 'type' => 'text', 'required' => false, 'autocomplete' => 'additional-name'],
        'last_name' => ['label' => 'Last Name', 'type' => 'text', 'required' => true, 'autocomplete' => 'family-name'],
        'employee_student_id' => ['label' => $idLabel, 'type' => 'text', 'required' => true, 'autocomplete' => 'off'],
        'email' => ['label' => 'Email Address', 'type' => 'email', 'required' => true, 'autocomplete' => 'email'],
        'contact_number' => ['label' => 'Contact Number', 'type' => 'tel', 'required' => false, 'autocomplete' => 'tel'],
        'department' => ['label' => $user->isStudent() ? 'Program or Department' : 'Department or Office', 'type' => 'text', 'required' => false, 'autocomplete' => 'organization-title'],
    ];

    $lockedCount = count(array_diff(array_keys($fields), $editable));
@endphp

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACCOUNT</span>
        <h1>My Profile</h1>
        <p>Your photo, personal details and password.</p>
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif


<div class="content-card form-card">

    <div class="form-card-header">
        <div>
            <h2>Profile</h2>
            <p>
                @if($lockedCount)
                    Your name, employee number and department are kept by the administrator, since they identify you in class schedules and records.
                    Ask the administrator if they need correcting.
                @else
                    Keep your details up to date.
                @endif
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">

        @csrf
        @method('PATCH')

        <div class="profile-photo-row">

            @if($user->profile_photo)
                <img src="{{ $user->profilePhotoUrl() }}" alt="Your profile photo" class="profile-photo-large" data-photo-preview>
            @else
                <span class="profile-photo-large profile-initials" data-photo-initials>{{ $initials ?: '?' }}</span>
                <img src="" alt="" class="profile-photo-large" data-photo-preview hidden>
            @endif

            <div class="form-group profile-photo-actions">

                <label for="photo">Profile photo</label>

                <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp,image/*" data-photo-input>

                <span class="form-help">JPG, PNG or WEBP, up to 5 MB. It is cropped to a square.</span>

                @if($user->profile_photo)
                    <div>
                        <button type="submit" form="remove-photo" class="btn btn-secondary">Remove Photo</button>
                    </div>
                @endif

                @error('photo')
                    <span class="form-error">{{ $message }}</span>
                @enderror

            </div>

        </div>

        <div class="form-row profile-fields">

            @foreach($fields as $name => $field)

                <div class="form-group">

                    <label for="{{ $name }}">
                        {{ $field['label'] }}
                        @if(in_array($name, $editable, true) && $field['required'])
                            <span class="required">*</span>
                        @endif
                    </label>

                    @if(in_array($name, $editable, true))

                        <input
                            type="{{ $field['type'] }}"
                            id="{{ $name }}"
                            name="{{ $name }}"
                            value="{{ old($name, $user->{$name}) }}"
                            autocomplete="{{ $field['autocomplete'] }}"
                            @if($field['required']) required @endif
                            @if($name === 'contact_number') placeholder="0917 123 4567" @endif
                        >

                        @error($name)
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    @else

                        <input type="text" id="{{ $name }}" value="{{ $user->{$name} }}" readonly class="profile-locked" aria-describedby="{{ $name }}-locked">

                        <span class="form-help" id="{{ $name }}-locked">Managed by the administrator.</span>

                    @endif

                </div>

            @endforeach

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </div>

    </form>

    @if($user->profile_photo)
        <form method="POST" action="{{ route('profile.photo.destroy') }}" id="remove-photo">
            @csrf
            @method('DELETE')
        </form>
    @endif

</div>


<div class="content-card form-card">

    <div class="form-card-header">
        <div>
            <h2>Change Password</h2>
            <p>Enter your current password, then choose a new one of at least 8 characters.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('profile.password') }}">

        @csrf
        @method('PUT')

        <div class="form-row profile-fields">

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                @error('current_password', 'password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required>
                @error('password', 'password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Change Password</button>
        </div>

    </form>

</div>

@endsection


@push('scripts')
<script>
    (function () {
        var input = document.querySelector('[data-photo-input]');
        var preview = document.querySelector('[data-photo-preview]');
        var initials = document.querySelector('[data-photo-initials]');

        if (!input || !preview) {
            return;
        }

        // Show the chosen photo before it is saved.
        input.addEventListener('change', function () {
            if (!input.files || !input.files[0]) {
                return;
            }

            preview.src = URL.createObjectURL(input.files[0]);
            preview.hidden = false;

            if (initials) {
                initials.hidden = true;
            }
        });
    })();
</script>
@endpush
