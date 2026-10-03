@extends('layouts.admin')

@section('title', 'Add Floor')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Add Floor</h1>

        <p>
            Create a floor and assign it to a building.
        </p>
    </div>

    <a href="{{ route('floors.index') }}" class="btn-secondary">
        ← Back to Floors
    </a>

</div>


<div class="content-card form-card">

    <form method="POST" action="{{ route('floors.store') }}">

        @csrf


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
                        {{ old('building_id') == $building->id ? 'selected' : '' }}
                    >

                        {{ $building->building_name }}

                        @if($building->campus)
                            — {{ $building->campus->campus_name }}
                        @endif

                    </option>

                @endforeach

            </select>

            @error('building_id')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>


        <div class="form-row">

            <div class="form-group">

                <label for="floor_number">
                    Floor Number
                </label>

                <input
                    type="text"
                    id="floor_number"
                    name="floor_number"
                    value="{{ old('floor_number') }}"
                    placeholder="e.g. 1, 2, 3, Ground"
                    required
                >

                @error('floor_number')
                    <span class="form-error">{{ $message }}</span>
                @enderror

            </div>


            <div class="form-group">

                <label>
                    Building
                </label>

                <input
                    type="text"
                    value="Selected above"
                    disabled
                >

            </div>

        </div>


        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Optional floor description..."
            >{{ old('description') }}</textarea>

            @error('description')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('floors.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button type="submit" class="btn-primary">
                Save Floor
            </button>

        </div>

    </form>

</div>

@endsection