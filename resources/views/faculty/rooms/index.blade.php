@extends('layouts.app')

@section('title', 'Find a Room')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM SESSIONS</span>

        <h1>Find a Room</h1>

        <p>
            Only use this if the room's QR code can't be scanned. You'll be asked why, and the session is marked as a manual start for the administrator.
        </p>

    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
            ← Back to Dashboard
        </a>
    </div>

</div>


<div class="content-card">

    <div class="table-toolbar">

        <div>
            <h2>Rooms</h2>

            <span class="table-count">
                {{ $rooms->total() }}
                room{{ $rooms->total() != 1 ? 's' : '' }}
            </span>
        </div>

        <form
            method="GET"
            action="{{ route('faculty.rooms.index') }}"
            class="table-search"
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Room name or building..."
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
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($rooms as $room)

                        <tr>

                            <td>
                                <strong>{{ $room->room_name }}</strong>
                            </td>

                            <td>
                                {{ $room->floor?->building?->building_name ?? '—' }}
                                ·
                                Floor {{ $room->floor?->floor_number ?? '—' }}
                                <br>
                                <small>{{ $room->floor?->building?->campus?->campus_name }}</small>
                            </td>

                            <td>
                                <span class="status-badge status-{{ $room->status }}">
                                    {{ ucfirst($room->status) }}
                                </span>
                            </td>

                            <td>
                                <a
                                    href="{{ route('faculty.rooms.start', $room) }}"
                                    class="btn btn-primary"
                                >
                                    Select
                                </a>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        {{ $rooms->links() }}

    @else

        <div class="empty-state">
            <div class="empty-state-icon">⌕</div>
            <h3>No rooms found</h3>
            <p>No rooms match "{{ $search }}". Try a room name or building.</p>
        </div>

    @endif

</div>

@endsection
