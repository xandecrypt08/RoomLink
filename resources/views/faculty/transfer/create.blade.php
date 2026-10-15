@extends('layouts.app')

@section('title', 'Classroom Transfer')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM SESSIONS</span>

        <h1>Classroom Transfer</h1>

        <p>
            Move your ongoing class to another classroom. Your session record continues in the new room.
        </p>

    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
            ← Back to Dashboard
        </a>
    </div>

</div>


@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        <strong>The class could not be transferred:</strong>

        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


@if (! $activeSession)

    <div class="content-card">
        <div class="empty-state">
            You have no active session. Start your class in its room before transferring it.
        </div>
    </div>

@else

    {{-- Current Session --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">
            <div>
                <span class="page-eyebrow">CURRENT SESSION</span>
                <h2>{{ $activeSession->room->room_name }}</h2>
            </div>

            <span class="status-badge status-occupied">
                In Session
            </span>
        </div>

        <div class="detail-list">

            <div class="detail-item">
                <span>Location</span>
                <strong>
                    {{ $activeSession->room->floor->building->campus->campus_name ?? '—' }}
                    ·
                    {{ $activeSession->room->floor->building->building_name ?? '—' }}
                    ·
                    Floor {{ $activeSession->room->floor->floor_number ?? '—' }}
                </strong>
            </div>

            <div class="detail-item">
                <span>Class</span>
                <strong>
                    @if ($activeSession->classSession)
                        {{ $activeSession->classSession->subject->subject_code }}
                        ·
                        {{ $activeSession->classSession->section->section_name }}
                        ·
                        until {{ $activeSession->classSession->end_time->format('g:i A') }}
                    @else
                        Temporary session
                    @endif
                </strong>
            </div>

            <div class="detail-item">
                <span>Started</span>
                <strong>{{ $activeSession->started_at->format('g:i A') }}</strong>
            </div>

        </div>

    </div>


    {{-- Transfer Form --}}

    <div class="content-card form-card">

        <div class="form-card-header">
            <div>
                <h2>Transfer to Another Room</h2>
                <p>
                    Only rooms that are free and not booked for another class before your class ends are listed.
                </p>
            </div>
        </div>

        @if ($rooms->isEmpty())

            <div class="empty-state">
                No classrooms are available for a transfer right now.
            </div>

        @else

            <form action="{{ route('faculty.transfer.store') }}" method="POST">
                @csrf

                <div class="form-grid">

                    <div class="form-group form-group-full">
                        <label for="room_id">
                            New Room <span class="required">*</span>
                        </label>

                        <select name="room_id" id="room_id" required>
                            <option value="">Select a room</option>

                            @foreach ($rooms as $room)
                                <option
                                    value="{{ $room->id }}"
                                    {{ old('room_id') == $room->id ? 'selected' : '' }}
                                >
                                    {{ $room->room_name }}
                                    · {{ $room->floor->building->building_name ?? '—' }}
                                    · Floor {{ $room->floor->floor_number ?? '—' }}
                                    · {{ $room->capacity }} seats
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="reason">
                            Reason
                        </label>

                        <textarea
                            name="reason"
                            id="reason"
                            rows="3"
                            maxlength="500"
                            placeholder="Optional, e.g. the projector stopped working."
                        >{{ old('reason') }}</textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Transfer Class
                    </button>
                </div>

            </form>

        @endif

    </div>

@endif

@endsection
