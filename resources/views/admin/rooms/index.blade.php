@extends('layouts.admin')

@section('title', 'Room Management')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Room Management</h1>

        <p>
            Manage classrooms, locations, capacity, and room status.
        </p>
    </div>

    <a href="{{ route('rooms.create') }}" class="btn-primary">
        + Add Room
    </a>

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


<div class="content-card">

    <div class="table-toolbar">

        <div>
            <h2>Rooms</h2>

            <span class="table-count">
                {{ $rooms->total() }}
                registered room{{ $rooms->total() != 1 ? 's' : '' }}
            </span>
        </div>


        <form
            method="GET"
            action="{{ route('rooms.index') }}"
            class="table-search"
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search rooms or buildings..."
            >

            <button type="submit" class="btn-secondary">
                Search
            </button>

        </form>

    </div>


    @if($rooms->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>
                        <th>Room</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Facilities</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($rooms as $room)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $room->room_name }}
                                </div>

                                <div class="table-secondary">
                                    {{ $room->qr_code ?? 'QR not generated' }}
                                </div>

                            </td>


                            <td>

                                <div class="table-primary">

                                    {{ $room->floor->building->building_name ?? '—' }}

                                </div>

                                <div class="table-secondary">

                                    Floor {{ $room->floor->floor_number ?? '—' }}

                                    @if($room->floor?->building?->campus)
                                        · {{ $room->floor->building->campus->campus_name }}
                                    @endif

                                </div>

                            </td>


                            <td>

                                {{ $room->capacity ?? '—' }}

                            </td>


                            <td>

                                @php
                                    $statusClass = match($room->status) {
                                        'available' => 'status-available',
                                        'occupied' => 'status-occupied',
                                        'maintenance' => 'status-maintenance',
                                        default => '',
                                    };
                                @endphp

                                <span class="room-status {{ $statusClass }}">
                                    {{ ucfirst($room->status) }}
                                </span>

                            </td>


                            <td>

                                <span class="count-badge">
                                    {{ $room->facilities->count() }}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('rooms.show', $room) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('rooms.edit', $room) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="{{ route('rooms.qr', $room) }}"
                                        class="action-view"
                                    >
                                        QR
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('rooms.destroy', $room) }}"
                                        onsubmit="return confirm('Delete this room?');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="action-delete"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        <div class="table-pagination">
            {{ $rooms->links() }}
        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">□</div>

            <h3>No rooms found</h3>

            <p>
                Add your first classroom to start managing room utilization.
            </p>

            <a
                href="{{ route('rooms.create') }}"
                class="btn-primary"
            >
                + Add Room
            </a>

        </div>

    @endif

</div>

@endsection