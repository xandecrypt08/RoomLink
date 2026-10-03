@extends('layouts.admin')

@section('title', 'Edit Facility')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Edit Facility</h1>

        <p>
            Update the facility information.
        </p>

    </div>

    <a
        href="{{ route('facilities.show', $facility) }}"
        class="btn-secondary"
    >
        ← Back to Facility
    </a>

</div>


@if($errors->any())

    <div class="alert-error">

        <strong>Please fix the following:</strong>

        <ul>

            @foreach($errors->all() as $error)

                <li>{{ $error }}</li>

            @endforeach

        </ul>

    </div>

@endif


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('facilities.update', $facility) }}"
    >

        @csrf
        @method('PUT')


        <div class="form-group">

            <label for="facility_name">
                Facility Name
            </label>

            <input
                type="text"
                id="facility_name"
                name="facility_name"
                value="{{ old('facility_name', $facility->facility_name) }}"
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
                href="{{ route('facilities.show', $facility) }}"
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

@endsection