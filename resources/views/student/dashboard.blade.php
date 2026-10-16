@extends('layouts.app')

@section('title', 'Classrooms')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">STUDENT PORTAL</span>

        <h1>Classrooms</h1>

        <p>
            Welcome back, {{ $student->full_name }}. See which classrooms are free right now.
        </p>

    </div>

</div>


{{-- Room Summary --}}

<div class="dashboard-stats">

    <div class="dashboard-stat-card">
        <div class="stat-icon room-icon"><x-icon name="door" /></div>
        <div class="stat-content">
            <span>Total Rooms</span>
            <strong>{{ $totalRooms }}</strong>
            <small>Registered classrooms</small>
        </div>
    </div>

    <div class="dashboard-stat-card">
        <div class="stat-icon stat-icon-available"><x-icon name="check-circle" /></div>
        <div class="stat-content">
            <span>Available</span>
            <strong>{{ $availableRooms }}</strong>
            <small>Free to use now</small>
        </div>
    </div>

    <div class="dashboard-stat-card">
        <div class="stat-icon stat-icon-occupied"><x-icon name="users" /></div>
        <div class="stat-content">
            <span>Occupied</span>
            <strong>{{ $occupiedRooms }}</strong>
            <small>Class in session</small>
        </div>
    </div>

    <div class="dashboard-stat-card">
        <div class="stat-icon stat-icon-maintenance"><x-icon name="wrench" /></div>
        <div class="stat-content">
            <span>Maintenance</span>
            <strong>{{ $maintenanceRooms }}</strong>
            <small>Unavailable</small>
        </div>
    </div>

</div>


{{-- Room Finder --}}

<div class="content-card">

    <div class="table-toolbar">
        <div>
            <h2>Find a Classroom</h2>
            <span class="table-count">
                {{ $totalRooms }} classroom{{ $totalRooms != 1 ? 's' : '' }}
            </span>
        </div>
    </div>

    @if($rooms->isEmpty())

        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="door" /></div>
            <h3>No classrooms yet</h3>
            <p>Classrooms appear here once the administrator adds them.</p>
        </div>

    @else

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($rooms as $room)

                        <tr>

                            <td>
                                <div class="table-primary">{{ $room->room_name }}</div>
                            </td>

                            <td>
                                <div class="table-primary">{{ $room->floor?->building?->building_name }}</div>
                                <div class="table-secondary">
                                    Floor {{ $room->floor?->floor_number }}
                                    · {{ $room->floor?->building?->campus?->campus_name }}
                                </div>
                            </td>

                            <td>
                                {{ $room->capacity ? $room->capacity.' seats' : '—' }}
                            </td>

                            <td>
                                <span class="room-status status-{{ $room->status }}">
                                    {{ ucfirst($room->status) }}
                                </span>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif

</div>

@endsection
