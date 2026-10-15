@extends('layouts.app')

@section('title', 'Start Classroom Session')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM SESSION</span>

        <h1>{{ $room?->room_name ?? 'Unknown Room' }}</h1>

        <p>
            @if($room)
                {{ $room->floor?->building?->campus?->campus_name }}
                ·
                {{ $room->floor?->building?->building_name }}
                ·
                Floor {{ $room->floor?->floor_number }}
            @else
                The scanned QR code was not recognized.
            @endif
        </p>

    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
            ← Dashboard
        </a>
    </div>

</div>

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif


<div class="content-card detail-card verify-card">

    <div class="detail-card-header">

        <div>
            <span class="page-eyebrow">ROOM VERIFICATION</span>
            <h2>{{ $blocker ? 'This class can’t be started' : 'Ready to start your class' }}</h2>
        </div>

        @if($room)
            <span class="room-status status-{{ $room->status }}">
                {{ ucfirst($room->status) }}
            </span>
        @endif

    </div>

    @if($approvedRequest)

        <div class="alert alert-success verify-message">
            Your temporary classroom request for this room was approved
            @if($approvedRequest->scheduledFaculty)
                by {{ $approvedRequest->scheduledFaculty->full_name }}.
            @else
                (no class was scheduled here).
            @endif
            @if(! $schedule)
                This will be recorded as a temporary session.
            @endif
        </div>

    @endif

    @if($schedule)

        <div class="detail-list">

            <div class="detail-item">
                <span>Subject</span>
                <strong>
                    {{ $schedule->subject->subject_code }}
                    · {{ $schedule->subject->subject_name }}
                </strong>
            </div>

            <div class="detail-item">
                <span>Section</span>
                <strong>{{ $schedule->section->section_name }}</strong>
            </div>

            <div class="detail-item">
                <span>Schedule</span>
                <strong>
                    {{ $schedule->start_time->format('g:i A') }}
                    – {{ $schedule->end_time->format('g:i A') }}
                </strong>
            </div>

            <div class="detail-item">
                <span>Faculty</span>
                <strong>{{ $schedule->faculty->full_name }}</strong>
            </div>

        </div>

    @endif

    @if($blocker)

        <div class="alert alert-error verify-message">
            {{ $blocker }}
        </div>

        <div class="form-actions">
            @if($room === null)
                <a href="{{ route('faculty.rooms.index') }}" class="btn btn-primary">
                    Find the Room Manually
                </a>
            @elseif(! $room->isUnderMaintenance())
                <a href="{{ route('faculty.temporary-requests.create', ['code' => $room->qr_code]) }}" class="btn btn-secondary">
                    Request This Room Temporarily
                </a>
            @endif
        </div>

    @else

        <form
            action="{{ route('faculty.sessions.store') }}"
            method="POST"
            class="form-actions"
        >
            @csrf

            <input type="hidden" name="code" value="{{ $code }}">

            <button type="submit" class="btn btn-primary btn-large">
                Start Session
            </button>
        </form>

    @endif

</div>

@endsection
