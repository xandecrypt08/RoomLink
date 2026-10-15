@extends('layouts.admin')

@section('title', 'Edit User Account')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">SYSTEM</span>

        <h1>Edit {{ $user->full_name }}</h1>

        <p>
            Update account details, reset the password, or change the account status.
        </p>

    </div>

    <a
        href="{{ route('users.index') }}"
        class="btn-secondary"
    >
        ← Back to User Accounts
    </a>

</div>


@if(session('success'))

    <div class="alert-success">
        {{ session('success') }}
    </div>

@endif


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('users.update', $user) }}"
    >

        @csrf
        @method('PUT')

        @include('admin.users.partials.fields')


        <div class="form-actions">

            <a
                href="{{ route('users.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Changes
            </button>

        </div>

    </form>

</div>


<div class="content-card form-card">

    <h2>Reset Password</h2>

    <p>
        Set a new password for this account and share it with the user.
    </p>

    <form
        method="POST"
        action="{{ route('users.password', $user) }}"
    >

        @csrf
        @method('PATCH')

        <div class="form-row">

            <div class="form-group">

                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="password"
                    autocomplete="new-password"
                    required
                >

                <span class="form-help">
                    At least 8 characters.
                </span>

                @error('password')
                    <span class="form-error">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="new_password_confirmation">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="new_password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                >

            </div>

        </div>


        <div class="form-actions">

            <button
                type="submit"
                class="btn-primary"
                onclick="return confirm('Reset this user\'s password?');"
            >
                Reset Password
            </button>

        </div>

    </form>

</div>

@endsection
