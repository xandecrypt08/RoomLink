@extends('layouts.admin')

@section('title', 'Add User Account')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">SYSTEM</span>

        <h1>Add User Account</h1>

        <p>
            Create a login account for an administrator, faculty member, or student.
        </p>

    </div>

    <a
        href="{{ route('users.index') }}"
        class="btn-secondary"
    >
        ← Back to User Accounts
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('users.store') }}"
    >

        @csrf

        @include('admin.users.partials.fields')


        <div class="form-row">

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
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

                <label for="password_confirmation">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                >

            </div>

        </div>


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
                Create Account
            </button>

        </div>

    </form>

</div>

@endsection
