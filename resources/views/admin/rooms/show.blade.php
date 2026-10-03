@extends('layouts.admin')

@section('title', 'Room Details')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>{{ $room->room_name }}</h1>

        <p>
            Classroom information, facilities, and scheduled sessions.
        </p>

    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('rooms.qr', $room) }}"
            class="btn-secondary"
        >
            View QR
        </a>

        <a
            href="{{ route('rooms.edit', $room) }}"
            class="btn-primary"
        >
            Edit Room
        </a>

        <a
            href="{{ route('rooms.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">

    {{-- ROOM INFORMATION --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    ROOM INFORMATION
                </span>

                <h2>Details</h2>

            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">

                <span>Room Name</span>

                <strong>
                    {{ $room->room_name }}
                </strong>

            </div>


            <div class="detail-item">

                <span>QR Identifier</span>

                <strong>
                    {{ $room->qr_code ?? 'Not generated' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Campus</span>

                <strong>
                    {{ $room->floor->building->campus->campus_name ?? '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Building</span>

                <strong>
                    {{ $room->floor->building->building_name ?? '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Floor</span>

                <strong>
                    Floor {{ $room->floor->floor_number ?? '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Capacity</span>

                <strong>
                    {{ $room->capacity ?? '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Status</span>

                <strong>
                    {{ ucfirst($room->status) }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Description</span>

                <strong>
                    {{ $room->description ?: 'No description provided.' }}
                </strong>

            </div>

        </div>

    </div>


    {{-- FACILITIES --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    CLASSROOM FACILITIES
                </span>

                <h2>Facilities</h2>

            </div>

            <span class="count-badge">
                {{ $room->facilities->count() }}
            </span>

        </div>


        @if($room->facilities->count())

            <div class="floor-list">

                @foreach($room->facilities as $facility)

                    <div class="floor-item">

                        <div class="floor-icon">
                            ✓
                        </div>

                        <div class="floor-info">

                            <strong>
                                {{ $facility->facility_name }}
                            </strong>

                            <span>
                                Available in this classroom
                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">

                <strong>No facilities assigned</strong>

                <span>
                    Edit this room to assign facilities.
                </span>

            </div>

        @endif

    </div>


    {{-- SCHEDULED SESSIONS --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    CLASS SCHEDULE
                </span>

                <h2>Sessions</h2>

            </div>

            <span class="count-badge">
                {{ $room->classSessions->count() }}
            </span>

        </div>


        @if($room->classSessions->count())

            <div class="floor-list">

                @foreach($room->classSessions as $session)

                    <div class="floor-item">

                        <div class="floor-icon">
                            ◷
                        </div>

                        <div class="floor-info">

                            <strong>
                                {{ $session->day }}
                            </strong>

                            <span>

                                {{ $session->start_time?->format('g:i A') }}
                                –
                                {{ $session->end_time?->format('g:i A') }}

                                ·

                                {{ $session->subject->subject_code ?? 'No subject' }}

                                ·

                                {{ $session->faculty->full_name ?? 'No faculty' }}

                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">

                <strong>No class sessions</strong>

                <span>
                    No schedules are currently assigned to this room.
                </span>

            </div>

        @endif

    </div>


    {{-- DESCRIPTION / QR --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    QR IDENTIFICATION
                </span>

                <h2>Room QR Code</h2>

            </div>

        </div>


        <div class="small-empty-state">

            <strong>
                {{ $room->qr_code ?? 'QR code not generated' }}
            </strong>

            <span>
                Use the QR code to identify this classroom.
            </span>

            <br>

            <a
                href="{{ route('rooms.qr', $room) }}"
                class="btn-primary"
            >
                Open QR Code
            </a>

        </div>

    </div>

</div>

@endsection