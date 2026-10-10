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

</div>

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

                        <td>

                            @if($currentClass && $currentClass->id === $schedule->id)

                                <span class="status-badge status-active">
                                    In Session
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

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="empty-state">
                            No classes scheduled for today.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection