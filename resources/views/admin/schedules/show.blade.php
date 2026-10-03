@extends('layouts.admin')

@section('title', 'Class Session Details')

@section('content')

<div class="page-header">
    <div>
        <span class="page-eyebrow">SCHEDULE MANAGEMENT</span>
        <h1>Class Session Details</h1>
        <p>View the complete information for this classroom session.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-primary">
            Edit Session
        </a>

        <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
            ← Back
        </a>
    </div>
</div>

<div class="details-grid">

    {{-- Schedule Overview --}}
    <div class="detail-card detail-card-wide">

        <div class="detail-card-header">
            <div>
                <span class="page-eyebrow">SCHEDULE</span>
                <h2>Class Session</h2>
            </div>

            @if ($schedule->status === 'active')
                <span class="room-status status-available">
                    Active
                </span>
            @else
                <span class="room-status status-maintenance">
                    Inactive
                </span>
            @endif
        </div>

        <div class="detail-list">

            <div class="detail-item">
                <span class="detail-label">Day</span>
                <strong>
                    @switch($schedule->day)
                        @case('M') Monday @break
                        @case('T') Tuesday @break
                        @case('W') Wednesday @break
                        @case('Th') Thursday @break
                        @case('F') Friday @break
                        @case('S') Saturday @break
                        @case('Su') Sunday @break
                        @default {{ $schedule->day }}
                    @endswitch
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Time</span>
                <strong>
                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                    –
                    {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Subject</span>
                <strong>
                    {{ $schedule->subject->subject_code }}
                </strong>
                <span>
                    {{ $schedule->subject->subject_name }}
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Section</span>
                <strong>
                    {{ $schedule->section->section_name }}
                </strong>
            </div>

        </div>
    </div>


    {{-- Classroom --}}
    <div class="detail-card">

        <div class="detail-card-header">
            <div>
                <span class="page-eyebrow">LOCATION</span>
                <h2>Classroom</h2>
            </div>
        </div>

        <div class="detail-list">

            <div class="detail-item">
                <span class="detail-label">Room</span>
                <strong>
                    {{ $schedule->room->room_name }}
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Building</span>
                <strong>
                    {{ $schedule->room->floor->building->building_name }}
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Floor</span>
                <strong>
                    Floor {{ $schedule->room->floor->floor_number }}
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Campus</span>
                <strong>
                    {{ $schedule->room->floor->building->campus->campus_name }}
                </strong>
            </div>

        </div>

        <div class="detail-card-footer">
            <a
                href="{{ route('rooms.show', $schedule->room) }}"
                class="btn btn-secondary"
            >
                View Classroom
            </a>
        </div>

    </div>


    {{-- Faculty --}}
    <div class="detail-card">

        <div class="detail-card-header">
            <div>
                <span class="page-eyebrow">FACULTY</span>
                <h2>Assigned Faculty</h2>
            </div>
        </div>

        <div class="detail-list">

            <div class="detail-item">
                <span class="detail-label">Name</span>
                <strong>
                    {{ $schedule->faculty->full_name }}
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Employee ID</span>
                <strong>
                    {{ $schedule->faculty->employee_id }}
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Department</span>
                <strong>
                    {{ $schedule->faculty->department ?: '—' }}
                </strong>
            </div>

            <div class="detail-item">
                <span class="detail-label">Email</span>
                <strong>
                    {{ $schedule->faculty->email ?: '—' }}
                </strong>
            </div>

        </div>

        <div class="detail-card-footer">
            <a
                href="{{ route('faculties.show', $schedule->faculty) }}"
                class="btn btn-secondary"
            >
                View Faculty
            </a>
        </div>

    </div>


    {{-- Facilities --}}
    <div class="detail-card">

        <div class="detail-card-header">
            <div>
                <span class="page-eyebrow">CLASSROOM</span>
                <h2>Facilities</h2>
            </div>
        </div>

        @if ($schedule->room->facilities->count())

            <div class="facility-options">

                @foreach ($schedule->room->facilities as $facility)

                    <div class="facility-option">
                        <span class="facility-check">✓</span>
                        <span>{{ $facility->facility_name }}</span>
                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">
                <p>No facilities have been assigned to this classroom.</p>
            </div>

        @endif

    </div>


    {{-- Description --}}
    @if ($schedule->description)

        <div class="detail-card detail-card-wide">

            <div class="detail-card-header">
                <div>
                    <span class="page-eyebrow">NOTES</span>
                    <h2>Description</h2>
                </div>
            </div>

            <p class="detail-description">
                {{ $schedule->description }}
            </p>

        </div>

    @endif

</div>

@endsection