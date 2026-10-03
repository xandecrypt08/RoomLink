@extends('layouts.admin')

@section('title', 'Add Faculty')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Add Faculty</h1>

        <p>
            Register a faculty member for classroom scheduling.
        </p>

    </div>

    <a
        href="{{ route('faculties.index') }}"
        class="btn-secondary"
    >
        ← Back to Faculty
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('faculties.store') }}"
    >

        @csrf


        <div class="form-row">

            <div class="form-group">

                <label for="employee_id">
                    Employee ID
                </label>

                <input
                    type="text"
                    id="employee_id"
                    name="employee_id"
                    value="{{ old('employee_id') }}"
                    placeholder="e.g. EMP-001"
                    required
                >

                @error('employee_id')
                    <span class="form-error">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="department">
                    Department
                </label>

                <input
                    type="text"
                    id="department"
                    name="department"
                    value="{{ old('department') }}"
                    placeholder="e.g. College of Education"
                >

                @error('department')
                    <span class="form-error">
                        {{ $message }}
                    </span>
                @enderror

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label for="first_name">
                    First Name
                </label>

                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="{{ old('first_name') }}"
                    required
                >

                @error('first_name')
                    <span class="form-error">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="middle_name">
                    Middle Name
                </label>

                <input
                    type="text"
                    id="middle_name"
                    name="middle_name"
                    value="{{ old('middle_name') }}"
                >

                @error('middle_name')
                    <span class="form-error">
                        {{ $message }}
                    </span>
                @enderror

            </div>

        </div>


        <div class="form-group">

            <label for="last_name">
                Last Name
            </label>

            <input
                type="text"
                id="last_name"
                name="last_name"
                value="{{ old('last_name') }}"
                required
            >

            @error('last_name')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        <div class="form-group">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="faculty@example.com"
            >

            @error('email')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('faculties.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Faculty
            </button>

        </div>

    </form>

</div>

@endsection