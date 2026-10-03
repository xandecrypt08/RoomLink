@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')

<div class="page-header">

    <div>

        <h1>Student Dashboard</h1>

        <p>
            Welcome back,
            {{ $student->full_name }}.
        </p>

    </div>

</div>


{{-- Room Summary --}}

<div class="dashboard-grid">

    <div class="dashboard-card">

        <div class="card-label">
            TOTAL ROOMS
        </div>

        <h2>
            {{ $totalRooms }}
        </h2>

        <p>
            Classrooms registered in RoomLink.
        </p>

    </div>


    <div class="dashboard-card">

        <div class="card-label">
            AVAILABLE
        </div>

        <h2>
            {{ $availableRooms }}
        </h2>

        <p>
            Rooms currently available.
        </p>

    </div>


    <div class="dashboard-card">

        <div class="card-label">
            OCCUPIED
        </div>

        <h2>
            {{ $occupiedRooms }}
        </h2>

        <p>
            Rooms currently occupied.
        </p>

    </div>


    <div class="dashboard-card">

        <div class="card-label">
            MAINTENANCE
        </div>

        <h2>
            {{ $maintenanceRooms }}
        </h2>

        <p>
            Rooms unavailable due to maintenance.
        </p>

    </div>

</div>


{{-- Room Finder --}}

<div class="content-card">

    <div class="section-header">

        <div>

            <h2>Find a Classroom</h2>

            <p>
                Check classroom availability and location.
            </p>

        </div>

    </div>


    <div class="table-container">

        <table class="data-table">

            <thead>

                <tr>
                    <th>Room</th>
                    <th>Campus</th>
                    <th>Building</th>
                    <th>Floor</th>
                    <th>Capacity</th>
                    <th>Status</th>
                </tr>

            </thead>

            <tbody>

                @forelse($rooms as $room)

                    <tr>

                        <td>
                            <strong>
                                {{ $room->room_name }}
                            </strong>
                        </td>

                        <td>
                            {{ $room->floor->building->campus->campus_name }}
                        </td>

                        <td>
                            {{ $room->floor->building->building_name }}
                        </td>

                        <td>
                            Floor {{ $room->floor->floor_number }}
                        </td>

                        <td>
                            {{ $room->capacity }}
                        </td>

                        <td>

                            @if($room->status === 'available')

                                <span class="status-badge status-active">
                                    Available
                                </span>

                            @elseif($room->status === 'occupied')

                                <span class="status-badge">
                                    Occupied
                                </span>

                            @else

                                <span class="status-badge">
                                    Maintenance
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="empty-state">
                            No classrooms available.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection