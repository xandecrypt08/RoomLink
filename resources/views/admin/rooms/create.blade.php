@extends('layouts.admin')

@section('title', 'Add Room')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Add Room</h1>

        <p>
            Create a classroom and assign its location and facilities.
        </p>
    </div>

    <a
        href="{{ route('rooms.index') }}"
        class="btn-secondary"
    >
        ← Back to Rooms
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('rooms.store') }}"
    >

        @csrf


        <div class="form-group">

            <label for="floor_id">
                Floor
            </label>

            <select
                id="floor_id"
                name="floor_id"
                required
            >

                <option value="">
                    Select floor
                </option>

                @foreach($floors as $floor)

                    <option
                        value="{{ $floor->id }}"
                        {{ old('floor_id') == $floor->id ? 'selected' : '' }}
                    >

                        {{ $floor->building->building_name ?? 'Unknown Building' }}

                        — Floor {{ $floor->floor_number }}

                        @if($floor->building?->campus)
                            — {{ $floor->building->campus->campus_name }}
                        @endif

                    </option>

                @endforeach

            </select>

            @error('floor_id')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>


        <div class="form-row">

            <div class="form-group">

                <label for="room_name">
                    Room Name
                </label>

                <input
                    type="text"
                    id="room_name"
                    name="room_name"
                    value="{{ old('room_name') }}"
                    placeholder="e.g. ACAD 201"
                    required
                >

                @error('room_name')
                    <span class="form-error">{{ $message }}</span>
                @enderror

            </div>


            <div class="form-group">

                <label for="capacity">
                    Capacity
                </label>

                <input
                    type="number"
                    id="capacity"
                    name="capacity"
                    value="{{ old('capacity') }}"
                    min="1"
                    placeholder="e.g. 40"
                >

                @error('capacity')
                    <span class="form-error">{{ $message }}</span>
                @enderror

            </div>

        </div>


        <div class="form-group">

            <label for="status">
                Room Status
            </label>

            <select
                id="status"
                name="status"
                required
            >

                <option
                    value="available"
                    {{ old('status', 'available') === 'available' ? 'selected' : '' }}
                >
                    Available
                </option>

                <option
                    value="maintenance"
                    {{ old('status') === 'maintenance' ? 'selected' : '' }}
                >
                    Maintenance
                </option>

            </select>

            @error('status')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>


        <div class="form-group">

            <label>
                Facilities
            </label>

            @if($facilities->count())

                <div class="facility-options">

                    @foreach($facilities as $facility)

                        <label class="facility-option">

                            <input
                                type="checkbox"
                                name="facilities[]"
                                value="{{ $facility->id }}"
                                {{ in_array($facility->id, old('facilities', [])) ? 'checked' : '' }}
                            >

                            <span>
                                {{ $facility->facility_name }}
                            </span>

                        </label>

                    @endforeach

                </div>

            @else

                <div class="form-note">
                    No facilities have been created yet.
                </div>

            @endif

        </div>


        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Optional classroom description..."
            >{{ old('description') }}</textarea>

            @error('description')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('rooms.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Room
            </button>

        </div>

    </form>

</div>

@endsection