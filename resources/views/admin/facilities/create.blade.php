@extends('layouts.admin')

@section('title', 'Add Facility')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Add Facility</h1>

        <p>
            Add a facility that can be assigned to classrooms.
        </p>

    </div>

    <a
        href="{{ route('facilities.index') }}"
        class="btn-secondary"
    >
        ← Back to Facilities
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('facilities.store') }}"
    >

        @csrf


        <div class="form-group">

            <label for="facility_name">
                Facility Name
            </label>

            <input
                type="text"
                id="facility_name"
                name="facility_name"
                value="{{ old('facility_name') }}"
                placeholder="e.g. Projector"
                required
            >

            @error('facility_name')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('facilities.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Facility
            </button>

        </div>

    </form>

</div>

@endsection