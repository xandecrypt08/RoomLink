@extends('layouts.admin')

@section('title', 'Add Class Session')

@section('content')

<div class="page-header">
    <div>
        <span class="page-eyebrow">SCHEDULE MANAGEMENT</span>
        <h1>Add Class Session</h1>
        <p>Create a new classroom schedule and assign its faculty, subject, and section.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
            ← Back to Class Sessions
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        <strong>Please check the following:</strong>

        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="content-card form-card">

    <div class="form-card-header">
        <div>
            <h2>Class Session Information</h2>
            <p>Enter the schedule details below.</p>
        </div>
    </div>

    <form action="{{ route('schedules.store') }}" method="POST">
        @csrf

        <div class="form-grid">

            {{-- Room --}}
            <div class="form-group">
                <label for="room_id">
                    Classroom <span class="required">*</span>
                </label>

                <select name="room_id" id="room_id" required>
                    <option value="">Select classroom</option>

                    @foreach ($rooms as $room)
                        <option
                            value="{{ $room->id }}"
                            {{ old('room_id') == $room->id ? 'selected' : '' }}
                        >
                            {{ $room->room_name }}
                            —
                            {{ $room->floor->building->building_name }}
                            / Floor {{ $room->floor->floor_number }}
                        </option>
                    @endforeach
                </select>

                <small class="form-note">
                    Select the classroom where the session will be held.
                </small>
            </div>

            {{-- Faculty --}}
            <div class="form-group">
                <label for="faculty_id">
                    Faculty <span class="required">*</span>
                </label>

                <select name="faculty_id" id="faculty_id" required>
                    <option value="">Select faculty</option>

                    @foreach ($faculties as $faculty)
                        <option
                            value="{{ $faculty->id }}"
                            {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}
                        >
                            {{ $faculty->full_name }}
                            @if ($faculty->department)
                                — {{ $faculty->department }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Subject --}}
            <div class="form-group">
                <label for="subject_id">
                    Subject <span class="required">*</span>
                </label>

                <select name="subject_id" id="subject_id" required>
                    <option value="">Select subject</option>

                    @foreach ($subjects as $subject)
                        <option
                            value="{{ $subject->id }}"
                            {{ old('subject_id') == $subject->id ? 'selected' : '' }}
                        >
                            {{ $subject->subject_code }}
                            — {{ $subject->subject_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Section --}}
            <div class="form-group">
                <label for="section_id">
                    Section <span class="required">*</span>
                </label>

                <select name="section_id" id="section_id" required>
                    <option value="">Select section</option>

                    @foreach ($sections as $section)
                        <option
                            value="{{ $section->id }}"
                            {{ old('section_id') == $section->id ? 'selected' : '' }}
                        >
                            {{ $section->section_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Day --}}
            <div class="form-group">
                <label for="day">
                    Day <span class="required">*</span>
                </label>

                <select name="day" id="day" required>
                    <option value="">Select day</option>

                    <option value="M" {{ old('day') == 'M' ? 'selected' : '' }}>
                        Monday
                    </option>

                    <option value="T" {{ old('day') == 'T' ? 'selected' : '' }}>
                        Tuesday
                    </option>

                    <option value="W" {{ old('day') == 'W' ? 'selected' : '' }}>
                        Wednesday
                    </option>

                    <option value="Th" {{ old('day') == 'Th' ? 'selected' : '' }}>
                        Thursday
                    </option>

                    <option value="F" {{ old('day') == 'F' ? 'selected' : '' }}>
                        Friday
                    </option>

                    <option value="S" {{ old('day') == 'S' ? 'selected' : '' }}>
                        Saturday
                    </option>

                    <option value="Su" {{ old('day') == 'Su' ? 'selected' : '' }}>
                        Sunday
                    </option>
                </select>
            </div>

            {{-- Start Time --}}
            <div class="form-group">
                <label for="start_time">
                    Start Time <span class="required">*</span>
                </label>

                <input
                    type="time"
                    name="start_time"
                    id="start_time"
                    value="{{ old('start_time') }}"
                    required
                >
            </div>

            {{-- End Time --}}
            <div class="form-group">
                <label for="end_time">
                    End Time <span class="required">*</span>
                </label>

                <input
                    type="time"
                    name="end_time"
                    id="end_time"
                    value="{{ old('end_time') }}"
                    required
                >
            </div>

            {{-- Status --}}
            <div class="form-group">
                <label for="status">
                    Status <span class="required">*</span>
                </label>

                <select name="status" id="status" required>
                    <option
                        value="active"
                        {{ old('status', 'active') == 'active' ? 'selected' : '' }}
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        {{ old('status') == 'inactive' ? 'selected' : '' }}
                    >
                        Inactive
                    </option>
                </select>

                <small class="form-note">
                    Only active sessions are considered for classroom availability.
                </small>
            </div>

            {{-- Description --}}
            <div class="form-group form-group-full">
                <label for="description">
                    Description
                </label>

                <textarea
                    name="description"
                    id="description"
                    rows="4"
                    placeholder="Optional notes about this class session..."
                >{{ old('description') }}</textarea>
            </div>

        </div>

        <div class="form-actions">
            <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                Create Class Session
            </button>
        </div>

    </form>

</div>

@endsection