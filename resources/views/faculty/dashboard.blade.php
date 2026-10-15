@extends('layouts.app')

@section('title', 'Faculty Dashboard')

@section('content')

<div class="page-header">

    <div>
        <h1>Faculty Dashboard</h1>

        <p>
            Welcome back,
            {{ $faculty->full_name }}.
        </p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.rooms.index') }}" class="btn btn-secondary">
            Find a Room
        </a>

        <a href="{{ route('faculty.temporary-requests.index') }}" class="btn btn-primary">
            Temporary Classroom Requests
        </a>
    </div>

</div>

@if(session('success'))
    <div class="alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert-error">
        {{ session('error') }}
    </div>
@endif

{{-- Active Session (RLNK-22) --}}

@if($activeSession)

<div class="content-card active-session-card">

    <div class="section-header">

        <div>
            <div class="card-label">
                ACTIVE SESSION
            </div>

            <h2>
                {{ $activeSession->room->room_name }}
            </h2>

            <p>
                {{ $activeSession->room->floor?->building?->building_name }}
            </p>
        </div>

        <span class="status-badge status-occupied">
            OCCUPIED
        </span>

    </div>

    @if($activeSession->classSession)

        <div class="card-detail">
            Subject:
            <strong>
                {{ $activeSession->classSession->subject->subject_code }}
                -
                {{ $activeSession->classSession->subject->subject_name }}
            </strong>
        </div>

        <div class="card-detail">
            Section:
            <strong>
                {{ $activeSession->classSession->section->section_name }}
            </strong>
        </div>

    @endif

    <div class="card-detail">
        Started:
        <strong>
            {{ $activeSession->started_at->format('g:i A') }}
            ({{ $activeSession->started_at->diffForHumans(null, true) }} ago)
        </strong>
    </div>

    <form
        action="{{ route('faculty.sessions.end', $activeSession) }}"
        method="POST"
        class="form-actions"
        onsubmit="return confirm('End your session in {{ addslashes($activeSession->room->room_name) }}? The room will be released for other classes.');"
    >
        @csrf

        <a href="{{ route('faculty.transfer.create') }}" class="btn btn-secondary">
            Transfer Class
        </a>

        <button type="submit" class="btn btn-delete">
            End Session
        </button>
    </form>

</div>

@endif

{{-- Current Class --}}

<div class="dashboard-grid">

    <div class="dashboard-card">

        <div class="card-label">
            CURRENT CLASS
        </div>

        @if($currentClass)

            <h2>
                {{ $currentClass->subject->subject_code }}
            </h2>

            <p>
                {{ $currentClass->subject->subject_name }}
            </p>

            <div class="card-detail">
                Room:
                <strong>
                    {{ $currentClass->room->room_name }}
                </strong>
            </div>

            <div class="card-detail">
                Section:
                <strong>
                    {{ $currentClass->section->section_name }}
                </strong>
            </div>

            <div class="card-detail">
                Time:
                <strong>
                    {{ \Carbon\Carbon::parse($currentClass->start_time)->format('g:i A') }}
                    -
                    {{ \Carbon\Carbon::parse($currentClass->end_time)->format('g:i A') }}
                </strong>
            </div>

            <div class="status-badge status-active">
                CLASS IN SESSION
            </div>

        @else

            <h2>No Active Class</h2>

            <p>
                You currently have no class in session.
            </p>

            <div class="status-badge">
                AVAILABLE
            </div>

        @endif

    </div>


    {{-- Next Class --}}

    <div class="dashboard-card">

        <div class="card-label">
            NEXT CLASS
        </div>

        @if($nextClass)

            <h2>
                {{ $nextClass->subject->subject_code }}
            </h2>

            <p>
                {{ $nextClass->subject->subject_name }}
            </p>

            <div class="card-detail">
                Room:
                <strong>
                    {{ $nextClass->room->room_name }}
                </strong>
            </div>

            <div class="card-detail">
                Section:
                <strong>
                    {{ $nextClass->section->section_name }}
                </strong>
            </div>

            <div class="card-detail">
                Time:
                <strong>
                    {{ \Carbon\Carbon::parse($nextClass->start_time)->format('g:i A') }}
                    -
                    {{ \Carbon\Carbon::parse($nextClass->end_time)->format('g:i A') }}
                </strong>
            </div>

        @else

            <h2>No More Classes</h2>

            <p>
                You have no more scheduled classes today.
            </p>

        @endif

    </div>

</div>


{{-- Today's Schedule --}}

<div class="content-card">

    <div class="section-header">

        <div>
            <h2>Today's Schedule</h2>

            <p>
                Your scheduled classes for today.
            </p>
        </div>

    </div>

    <div class="table-container">

        <table class="data-table">

            <thead>

                <tr>
                    <th>Time</th>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Room</th>
                    <th>Status</th>
                    <th></th>
                </tr>

            </thead>

            <tbody>

                @forelse($todaySchedules as $schedule)

                    <tr>

                        <td>
                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                            -
                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                        </td>

                        <td>

                            <strong>
                                {{ $schedule->subject->subject_code }}
                            </strong>

                            <br>

                            <small>
                                {{ $schedule->subject->subject_name }}
                            </small>

                        </td>

                        <td>
                            {{ $schedule->section->section_name }}
                        </td>

                        <td>
                            {{ $schedule->room->room_name }}
                        </td>

                        @php
                            $sessionStatuses = $todaysSessionStatuses->get($schedule->id, collect());
                            $isRunning = $activeSession && (int) $activeSession->class_session_id === (int) $schedule->id;
                            $canMarkNotHeld = ! $sessionStatuses->contains('not_held')
                                && $sessionStatuses->every(fn ($status) => $status === 'active');
                        @endphp

                        <td>

                            @if($sessionStatuses->contains('not_held'))

                                <span class="status-badge status-maintenance">
                                    Not Held
                                </span>

                            @elseif($isRunning)

                                <span class="status-badge status-active">
                                    In Session
                                </span>

                            @elseif($sessionStatuses->isNotEmpty())

                                <span class="status-badge">
                                    Held
                                </span>

                            @elseif($currentClass && $currentClass->id === $schedule->id)

                                <span class="status-badge status-active">
                                    Now
                                </span>

                            @elseif($schedule->start_time->format('H:i:s') > now()->format('H:i:s'))

                                <span class="status-badge">
                                    Upcoming
                                </span>

                            @else

                                <span class="status-badge">
                                    Completed
                                </span>

                            @endif

                        </td>

                        <td>
                            @if($canMarkNotHeld)
                                <a href="{{ route('faculty.not-held.create', $schedule) }}" class="btn btn-secondary">
                                    Mark Not Held
                                </a>
                            @endif
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="empty-state">
                            No classes scheduled for today.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection