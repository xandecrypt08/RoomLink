@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

{{-- PAGE HEADER --}}
<div class="dashboard-header">

    <div>
        <span class="dashboard-eyebrow">ROOMLINK ADMINISTRATION</span>

        <h1>Dashboard</h1>

        <p>
            Overview of classroom spaces, schedules, and room operations.
        </p>
    </div>

    <div class="dashboard-date">
        <span>Today</span>
        <strong>{{ now()->format('F d, Y') }}</strong>
    </div>

</div>


{{-- SUMMARY CARDS --}}
<div class="dashboard-stats">

    <div class="dashboard-stat-card">

        <div class="stat-icon campus-icon">
            🏫
        </div>

        <div class="stat-content">
            <span>Total Campuses</span>
            <strong>{{ $totalCampuses }}</strong>
            <small>Registered campuses</small>
        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon building-icon">
            🏢
        </div>

        <div class="stat-content">
            <span>Total Buildings</span>
            <strong>{{ $totalBuildings }}</strong>
            <small>Registered buildings</small>
        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon floor-icon">
            🧱
        </div>

        <div class="stat-content">
            <span>Total Floors</span>
            <strong>{{ $totalFloors }}</strong>
            <small>Registered floors</small>
        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon room-icon">
            🚪
        </div>

        <div class="stat-content">
            <span>Total Rooms</span>
            <strong>{{ $totalRooms }}</strong>
            <small>Registered classrooms</small>
        </div>

    </div>

</div>


{{-- MAIN DASHBOARD GRID --}}
<div class="dashboard-grid">


    {{-- ROOM STATUS --}}
    <div class="dashboard-panel">

        <div class="panel-header">

            <div>
                <span class="panel-eyebrow">CLASSROOMS</span>
                <h2>Room Status</h2>
            </div>

            <a href="{{ route('rooms.index') }}" class="panel-link">
                View Rooms →
            </a>

        </div>


        <div class="room-status-list">

            {{-- Available --}}
            <div class="room-status-row">

                <div class="room-status-label">
                    <span class="status-dot available"></span>

                    <div>
                        <strong>Available</strong>
                        <small>Ready for use</small>
                    </div>
                </div>

                <strong class="room-status-number">
                    {{ $availableRooms }}
                </strong>

            </div>


            {{-- Occupied --}}
            <div class="room-status-row">

                <div class="room-status-label">
                    <span class="status-dot occupied"></span>

                    <div>
                        <strong>Occupied</strong>
                        <small>Currently in use</small>
                    </div>
                </div>

                <strong class="room-status-number">
                    {{ $occupiedRooms }}
                </strong>

            </div>


            {{-- Maintenance --}}
            <div class="room-status-row">

                <div class="room-status-label">
                    <span class="status-dot maintenance"></span>

                    <div>
                        <strong>Maintenance</strong>
                        <small>Unavailable</small>
                    </div>
                </div>

                <strong class="room-status-number">
                    {{ $maintenanceRooms }}
                </strong>

            </div>

        </div>


        {{-- STATUS BAR --}}
        @php
            $roomTotal = max($totalRooms, 1);

            $availablePercentage = ($availableRooms / $roomTotal) * 100;
            $occupiedPercentage = ($occupiedRooms / $roomTotal) * 100;
            $maintenancePercentage = ($maintenanceRooms / $roomTotal) * 100;
        @endphp

        <div class="room-status-bar">

            @if($availablePercentage > 0)
                <div
                    class="status-bar-available"
                    style="width: {{ $availablePercentage }}%"
                ></div>
            @endif

            @if($occupiedPercentage > 0)
                <div
                    class="status-bar-occupied"
                    style="width: {{ $occupiedPercentage }}%"
                ></div>
            @endif

            @if($maintenancePercentage > 0)
                <div
                    class="status-bar-maintenance"
                    style="width: {{ $maintenancePercentage }}%"
                ></div>
            @endif

        </div>

    </div>


    {{-- QUICK OVERVIEW --}}
    <div class="dashboard-panel">

        <div class="panel-header">

            <div>
                <span class="panel-eyebrow">ROOM OPERATIONS</span>
                <h2>Availability Overview</h2>
            </div>

        </div>


        <div class="availability-content">

            <div class="availability-number">
                <strong>{{ $availableRooms }}</strong>
                <span>available rooms</span>
            </div>


            <div class="availability-details">

                <div class="availability-item">
                    <span class="status-dot available"></span>
                    <span>Available</span>
                    <strong>{{ $availableRooms }}</strong>
                </div>

                <div class="availability-item">
                    <span class="status-dot occupied"></span>
                    <span>Occupied</span>
                    <strong>{{ $occupiedRooms }}</strong>
                </div>

                <div class="availability-item">
                    <span class="status-dot maintenance"></span>
                    <span>Maintenance</span>
                    <strong>{{ $maintenanceRooms }}</strong>
                </div>

            </div>

        </div>

    </div>


</div>


{{-- RECENT CLASS SESSIONS --}}
<div class="dashboard-panel sessions-panel">

    <div class="panel-header">

        <div>
            <span class="panel-eyebrow">SCHEDULES</span>
            <h2>Recent Class Sessions</h2>
        </div>

        <a href="{{ route('schedules.index') }}" class="panel-link">
            View Schedules →
        </a>

    </div>


    @if($recentSessions->count())

        <div class="session-list">

            @foreach($recentSessions as $session)

                <div class="session-row">

                    <div class="session-room">
                        <strong>{{ $session->room->room_name ?? 'N/A' }}</strong>

                        <span>
                            {{ $session->room->floor->building->building_name ?? 'N/A' }}
                        </span>
                    </div>


                    <div class="session-subject">

                        <strong>
                            {{ $session->subject->subject_code ?? 'N/A' }}
                        </strong>

                        <span>
                            {{ $session->subject->subject_name ?? 'No subject name' }}
                        </span>

                    </div>


                    <div class="session-faculty">

                        <strong>
                            {{ $session->faculty->full_name ?? 'N/A' }}
                        </strong>

                        <span>
                            {{ $session->section->section_name ?? 'No section' }}
                        </span>

                    </div>


                    <div class="session-time">

                        <strong>
                            {{ ucfirst($session->day) }}
                        </strong>

                        <span>
                            {{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }}
                            –
                            {{ \Carbon\Carbon::parse($session->end_time)->format('g:i A') }}
                        </span>

                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="dashboard-empty">

            <div class="empty-icon">📅</div>

            <strong>No class sessions yet</strong>

            <p>
                Create a class schedule to see classroom activity here.
            </p>

            <a href="{{ route('schedules.create') }}" class="empty-action">
                Create Schedule
            </a>

        </div>

    @endif

</div>


@endsection