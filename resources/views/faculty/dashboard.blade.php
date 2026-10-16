@extends('layouts.app')

@section('title', 'Faculty Dashboard')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">FACULTY PORTAL</span>

        <h1>Welcome back, {{ $faculty->first_name }}</h1>

        <p>
            Your classes and classroom sessions for {{ now()->format('l, F j') }}.
        </p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.rooms.index') }}" class="btn btn-secondary">
            Find a Room
        </a>

        <a href="{{ route('faculty.temporary-requests.index') }}" class="btn btn-primary">
            Room Requests
        </a>
    </div>

</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@if($facultyRecord === null)
    <div class="alert alert-error">
        Your account is not linked to a faculty record yet, so no classes can be shown. Please contact the administrator.
    </div>
@endif


{{-- Active Session (RLNK-21/22) --}}

@if($activeSession)

    <div class="dashboard-panel active-session-card">

        <div class="panel-header">

            <div>
                <span class="panel-eyebrow">ACTIVE SESSION</span>

                <h2>{{ $activeSession->room->room_name }}</h2>

                <p class="panel-subtitle">
                    {{ $activeSession->room->floor?->building?->building_name }}
                    · Floor {{ $activeSession->room->floor?->floor_number }}
                </p>
            </div>

            <span class="room-status status-occupied">
                In Session
            </span>

        </div>

        <div class="session-facts">

            @if($activeSession->classSession)
                <div>
                    <span>Class</span>
                    <strong>
                        {{ $activeSession->classSession->subject->subject_code }}
                        · {{ $activeSession->classSession->section->section_name }}
                    </strong>
                </div>
            @else
                <div>
                    <span>Class</span>
                    <strong>Temporary session</strong>
                </div>
            @endif

            <div>
                <span>Started</span>
                <strong>
                    {{ $activeSession->started_at->format('g:i A') }}
                    ({{ $activeSession->started_at->diffForHumans(null, true) }} ago)
                </strong>
            </div>

        </div>

        <form
            action="{{ route('faculty.sessions.end', $activeSession) }}"
            method="POST"
            class="panel-actions"
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


{{-- Current & Next Class --}}

<div class="dashboard-grid">

    <div class="dashboard-panel">

        <span class="panel-eyebrow">CURRENT CLASS</span>

        @if($currentClass)

            <h2 class="panel-title">
                {{ $currentClass->subject->subject_code }}
            </h2>

            <p class="panel-subtitle">{{ $currentClass->subject->subject_name }}</p>

            <div class="session-facts">
                <div>
                    <span>Room</span>
                    <strong>{{ $currentClass->room->room_name }}</strong>
                </div>
                <div>
                    <span>Section</span>
                    <strong>{{ $currentClass->section->section_name }}</strong>
                </div>
                <div>
                    <span>Time</span>
                    <strong>
                        {{ $currentClass->start_time->format('g:i A') }}
                        – {{ $currentClass->end_time->format('g:i A') }}
                    </strong>
                </div>
            </div>

            @if(! $activeSession)
                <div class="scan-hint">
                    <span class="scan-hint-icon" aria-hidden="true">▣</span>
                    <span>
                        To start this class, scan the QR code posted in
                        <strong>{{ $currentClass->room->room_name }}</strong>.
                        <a href="{{ route('faculty.rooms.index') }}">Can't scan it?</a>
                    </span>
                </div>

                <div class="panel-actions">
                    @include('faculty.partials.qr-scanner')
                </div>
            @endif

        @else

            <h2 class="panel-title">No class right now</h2>

            <p class="panel-subtitle">You have no class scheduled at this time.</p>

        @endif

    </div>


    <div class="dashboard-panel">

        <span class="panel-eyebrow">NEXT CLASS</span>

        @if($nextClass)

            <h2 class="panel-title">
                {{ $nextClass->subject->subject_code }}
            </h2>

            <p class="panel-subtitle">{{ $nextClass->subject->subject_name }}</p>

            <div class="session-facts">
                <div>
                    <span>Room</span>
                    <strong>{{ $nextClass->room->room_name }}</strong>
                </div>
                <div>
                    <span>Section</span>
                    <strong>{{ $nextClass->section->section_name }}</strong>
                </div>
                <div>
                    <span>Time</span>
                    <strong>
                        {{ $nextClass->start_time->format('g:i A') }}
                        – {{ $nextClass->end_time->format('g:i A') }}
                    </strong>
                </div>
            </div>

        @else

            <h2 class="panel-title">No more classes</h2>

            <p class="panel-subtitle">You have no more classes scheduled today.</p>

        @endif

    </div>

</div>


{{-- Today's Schedule --}}

<div class="content-card">

    <div class="table-toolbar">
        <div>
            <h2>Today's Schedule</h2>
            <span class="table-count">
                {{ $todaySchedules->count() }} class{{ $todaySchedules->count() != 1 ? 'es' : '' }} on {{ now()->format('l') }}
            </span>
        </div>
    </div>

    @if($todaySchedules->isEmpty())

        <div class="empty-state">
            <div class="empty-state-icon">◷</div>
            <h3>No classes today</h3>
            <p>Enjoy the free day. Classes scheduled for today will appear here.</p>
        </div>

    @else

        <div class="table-responsive">

            <table class="roomlink-table">

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

                    @foreach($todaySchedules as $schedule)

                        @php
                            $sessionStatuses = $todaysSessionStatuses->get($schedule->id, collect());
                            $isRunning = $activeSession && (int) $activeSession->class_session_id === (int) $schedule->id;
                            $canMarkNotHeld = ! $sessionStatuses->contains('not_held')
                                && $sessionStatuses->every(fn ($status) => $status === 'active');
                        @endphp

                        <tr>

                            <td class="nowrap">
                                <div class="table-primary">{{ $schedule->start_time->format('g:i A') }}</div>
                                <div class="table-secondary">until {{ $schedule->end_time->format('g:i A') }}</div>
                            </td>

                            <td>
                                <div class="table-primary">{{ $schedule->subject->subject_code }}</div>
                                <div class="table-secondary">{{ $schedule->subject->subject_name }}</div>
                            </td>

                            <td>{{ $schedule->section->section_name }}</td>

                            <td>{{ $schedule->room->room_name }}</td>

                            <td>
                                @if($sessionStatuses->contains('not_held'))
                                    <span class="room-status status-maintenance">Not Held</span>
                                @elseif($isRunning)
                                    <span class="room-status status-occupied">In Session</span>
                                @elseif($sessionStatuses->isNotEmpty())
                                    <span class="room-status status-available">Held</span>
                                @elseif($currentClass && $currentClass->id === $schedule->id)
                                    <span class="room-status status-occupied">Now</span>
                                @elseif($schedule->start_time->format('H:i:s') > now()->format('H:i:s'))
                                    <span class="room-status status-neutral">Upcoming</span>
                                @else
                                    <span class="room-status status-neutral">Ended</span>
                                @endif
                            </td>

                            <td class="table-actions-cell">
                                @if($canMarkNotHeld)
                                    <a href="{{ route('faculty.not-held.create', $schedule) }}" class="action-delete table-action">
                                        Mark Not Held
                                    </a>
                                @endif
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif

</div>

@endsection
