@extends('layouts.admin')

@section('title', 'Add Building')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Add Building</h1>

        <p>
            Add a building and assign it to a campus.
        </p>
    </div>

    <a
        href="{{ route('buildings.index') }}"
        class="btn-secondary"
    >
        ← Back to Buildings
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('buildings.store') }}"
    >

        @csrf


        {{-- CAMPUS --}}

        <div class="form-group">

            <label for="campus_id">
                Campus
            </label>

            <select
                id="campus_id"
                name="campus_id"
                required
            >

                <option value="">
                    Select a campus
                </option>

                @foreach($campuses as $campus)

                    <option
                        value="{{ $campus->id }}"
                        {{ old('campus_id') == $campus->id ? 'selected' : '' }}
                    >
                        {{ $campus->campus_name }}
                    </option>

                @endforeach

            </select>

            @error('campus_id')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        {{-- BUILDING NAME --}}

        <div class="form-group">

            <label for="building_name">
                Building Name
            </label>

            <input
                type="text"
                id="building_name"
                name="building_name"
                value="{{ old('building_name') }}"
                placeholder="e.g. Academic Building"
                required
            >

            @error('building_name')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        {{-- BUILDING CODE --}}

        <div class="form-group">

            <label for="building_code">
                Building Code
            </label>

            <input
                type="text"
                id="building_code"
                name="building_code"
                value="{{ old('building_code') }}"
                placeholder="e.g. ACAD"
            >

            @error('building_code')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        {{-- NUMBER OF FLOORS --}}

        <div class="form-group">

            <label for="number_of_floors">
                Number of Floors
            </label>

            <input
                type="number"
                id="number_of_floors"
                name="number_of_floors"
                value="{{ old('number_of_floors', 1) }}"
                min="1"
                required
            >

            @error('number_of_floors')
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
                placeholder="Optional building description"
            >{{ old('description') }}</textarea>

            @error('description')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        {{-- ACTIONS --}}

        <div class="form-actions">

            <a
                href="{{ route('buildings.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Building
            </button>

        </div>

    </form>

</div>

@endsection