@extends('layouts.admin')

@section('title', 'Edit Floor')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Edit Floor</h1>

        <p>
            Update the floor information and building assignment.
        </p>

    </div>

    <a
        href="{{ route('floors.show', $floor) }}"
        class="btn-secondary"
    >
        ← Back to Floor
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
        action="{{ route('floors.update', $floor) }}"
    >

        @csrf
        @method('PUT')


        {{-- BUILDING --}}

        <div class="form-group">

            <label for="building_id">
                Building
            </label>

            <select
                id="building_id"
                name="building_id"
                required
            >

                <option value="">
                    Select building
                </option>

                @foreach($buildings as $building)

                    <option
                        value="{{ $building->id }}"
                        {{ old('building_id', $floor->building_id) == $building->id ? 'selected' : '' }}
                    >

                        {{ $building->building_name }}

                        @if($building->campus)

                            — {{ $building->campus->campus_name }}

                        @endif

                    </option>

                @endforeach

            </select>

            @error('building_id')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        {{-- FLOOR NUMBER --}}

        <div class="form-group">

            <label for="floor_number">
                Floor Number
            </label>

            <input
                type="text"
                id="floor_number"
                name="floor_number"
                value="{{ old('floor_number', $floor->floor_number) }}"
                placeholder="e.g. 1, 2, 3, Ground"
                required
            >

            @error('floor_number')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        {{-- DESCRIPTION --}}

        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Optional floor description..."
            >{{ old('description', $floor->description) }}</textarea>

            @error('description')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        {{-- ACTIONS --}}

        <div class="form-actions">

            <a
                href="{{ route('floors.show', $floor) }}"
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