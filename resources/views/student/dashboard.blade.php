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
            <span class="table-count" data-room-count>
                {{ $totalRooms }} classroom{{ $totalRooms != 1 ? 's' : '' }}
            </span>
        </div>

        <div class="table-search">
            <input type="search" placeholder="Search room, building or facility..." aria-label="Search classrooms" data-room-search>

            <select aria-label="Campus" data-room-campus>
                <option value="">All campuses</option>
                @foreach($campuses as $campusId => $campusName)
                    <option value="{{ $campusId }}">{{ $campusName }}</option>
                @endforeach
            </select>

            <label class="free-now">
                <input type="checkbox" data-room-free>
                Free now
            </label>
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

                        <tr
                            data-room
                            data-campus="{{ $room->floor?->building?->campus_id }}"
                            data-free="{{ $availability[$room->id]['free'] ? '1' : '' }}"
                            data-search="{{ mb_strtolower(implode(' ', [$room->room_name, $room->floor?->building?->building_name, ...$room->facilities->pluck('facility_name')])) }}"
                        >

                            <td>
                                <div class="table-primary">{{ $room->room_name }}</div>
                                <a href="{{ route('timetable', ['type' => 'room', 'id' => $room->id]) }}" class="table-secondary">Timetable</a>
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
                                <div class="table-secondary">{{ $room->facilities->pluck('facility_name')->implode(', ') }}</div>
                            </td>

                            <td>
                                <span class="room-status status-{{ $room->status }}">
                                    {{ ucfirst($room->status) }}
                                </span>
                                @if($availability[$room->id]['note'])
                                    <div class="table-secondary">{{ $availability[$room->id]['note'] }}</div>
                                @endif
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="small-empty-state" data-no-results hidden>
            <strong>No classroom matches</strong>
            <span>Try another campus or search, or untick Free now.</span>
        </div>

    @endif

</div>

@endsection


@push('scripts')
<script>
    (function () {
        var rows = Array.prototype.slice.call(document.querySelectorAll('[data-room]'));
        var search = document.querySelector('[data-room-search]');
        var campus = document.querySelector('[data-room-campus]');
        var free = document.querySelector('[data-room-free]');

        if (!rows.length) {
            return;
        }

        function filter() {
            var text = search.value.trim().toLowerCase();
            var shown = 0;

            rows.forEach(function (row) {
                var match = row.dataset.search.indexOf(text) !== -1
                    && (!campus.value || row.dataset.campus === campus.value)
                    && (!free.checked || row.dataset.free === '1');

                row.hidden = !match;
                shown += match ? 1 : 0;
            });

            document.querySelector('[data-room-count]').textContent = (shown === rows.length ? '' : shown + ' of ')
                + rows.length + ' classroom' + (rows.length === 1 ? '' : 's');
            document.querySelector('[data-no-results]').hidden = shown > 0;
        }

        [search, campus, free].forEach(function (control) { control.addEventListener('input', filter); });
        // The browser may bring back earlier choices after going back.
        window.addEventListener('pageshow', filter);
    })();
</script>
@endpush
