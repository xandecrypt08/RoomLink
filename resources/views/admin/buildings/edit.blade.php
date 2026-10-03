@extends('layouts.admin')

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Building</h1>
        <p>Update building information.</p>
    </div>
</div>

<div class="form-card">

    <form method="POST" action="{{ route('buildings.update', $building) }}">

        @csrf
        @method('PUT')

        <div class="form-group">

            <label for="campus_id">Campus</label>

            <select id="campus_id" name="campus_id" required>

                @foreach($campuses as $campus)

                    <option
                        value="{{ $campus->id }}"
                        {{ old('campus_id', $building->campus_id) == $campus->id ? 'selected' : '' }}
                    >
                        {{ $campus->campus_name }}
                    </option>

                @endforeach

            </select>

            @error('campus_id')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>

        <div class="form-group">

            <label for="building_name">Building Name</label>

            <input
                type="text"
                id="building_name"
                name="building_name"
                value="{{ old('building_name', $building->building_name) }}"
                required
            >

            @error('building_name')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>

        <div class="form-group">

            <label for="building_code">Building Code</label>

            <input
                type="text"
                id="building_code"
                name="building_code"
                value="{{ old('building_code', $building->building_code) }}"
                required
            >

            @error('building_code')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>

        <div class="form-group">

            <label for="number_of_floors">Number of Floors</label>

            <input
                type="number"
                id="number_of_floors"
                name="number_of_floors"
                value="{{ old('number_of_floors', $building->number_of_floors) }}"
                min="1"
                max="100"
                required
            >

            @error('number_of_floors')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>

        <div class="form-group">

            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                rows="4"
            >{{ old('description', $building->description) }}</textarea>

            @error('description')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>

        <div class="form-actions">

            <a
                href="{{ route('buildings.index') }}"
                class="btn-light"
            >
                Cancel
            </a>

            <button type="submit" class="btn-primary">
                Update Building
            </button>

        </div>

    </form>

</div>

@endsection