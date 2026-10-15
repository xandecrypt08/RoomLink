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
                &middot;
                {{ $room->floor?->building?->building_name }}
                &middot;
                Floor {{ $room->floor?->floor_number }}
            @else
                The scanned QR code was not recognized.
            @endif
        </p>

    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.dashboard') }}" class="btn-secondary">
            Back to Dashboard
        </a>
    </div>

</div>

@if(session('error'))
    <div class="alert-error">
        {{ session('error') }}
    </div>
@endif

<div class="content-card">

    @if($room)

        <div class="section-header">

            <div>
                <h2>Room Verification</h2>

                <p>
                    Confirm the details below before starting your class.
                </p>
            </div>

            <span class="status-badge status-{{ $room->status }}">
                {{ strtoupper($room->status) }}
            </span>

        </div>

        @if($schedule)

            <div class="card-detail">
                Subject:
                <strong>
                    {{ $schedule->subject->subject_code }}
                    -
                    {{ $schedule->subject->subject_name }}
                </strong>
            </div>

            <div class="card-detail">
                Section:
                <strong>
                    {{ $schedule->section->section_name }}
                </strong>
            </div>

            <div class="card-detail">
                Schedule:
                <strong>
                    {{ $schedule->start_time->format('g:i A') }}
                    -
                    {{ $schedule->end_time->format('g:i A') }}
                </strong>
            </div>

            <div class="card-detail">
                Faculty:
                <strong>
                    {{ $schedule->faculty->full_name }}
                </strong>
            </div>

        @endif

    @endif

    @if($blocker)

        <div class="alert-error">
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

            <button type="submit" class="btn btn-primary">
                Start Session
            </button>
        </form>

    @endif

</div>

@endsection
