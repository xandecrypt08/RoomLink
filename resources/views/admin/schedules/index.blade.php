@extends('layouts.admin')

@section('title', 'Class Sessions')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Class Sessions</h1>

        <p>
            Manage classroom schedules and assignments.
        </p>

    </div>

    <a
        href="{{ route('schedules.create') }}"
        class="btn-primary"
    >
        + Add Class Session
    </a>

</div>


@if(session('success'))

    <div class="alert-success">
        {{ session('success') }}
    </div>

@endif


<div class="content-card">

    <div class="table-toolbar">

        <div>

            <h2>Schedules</h2>

            <span class="table-count">
                {{ $schedules->total() }}
                class session{{ $schedules->total() != 1 ? 's' : '' }}
            </span>

        </div>


        <form
            method="GET"
            action="{{ route('schedules.index') }}"
            class="schedule-filters"
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search room, faculty, subject..."
            >


            <select name="day">

                <option value="">
                    All Days
                </option>

                <option value="M" {{ $day === 'M' ? 'selected' : '' }}>
                    Monday
                </option>

                <option value="T" {{ $day === 'T' ? 'selected' : '' }}>
                    Tuesday
                </option>

                <option value="W" {{ $day === 'W' ? 'selected' : '' }}>
                    Wednesday
                </option>

                <option value="Th" {{ $day === 'Th' ? 'selected' : '' }}>
                    Thursday
                </option>

                <option value="F" {{ $day === 'F' ? 'selected' : '' }}>
                    Friday
                </option>

                <option value="S" {{ $day === 'S' ? 'selected' : '' }}>
                    Saturday
                </option>

                <option value="Su" {{ $day === 'Su' ? 'selected' : '' }}>
                    Sunday
                </option>

            </select>


            <select name="status">

                <option value="">
                    All Status
                </option>

                <option
                    value="active"
                    {{ $status === 'active' ? 'selected' : '' }}
                >
                    Active
                </option>

                <option
                    value="inactive"
                    {{ $status === 'inactive' ? 'selected' : '' }}
                >
                    Inactive
                </option>

            </select>


            <button
                type="submit"
                class="btn-secondary"
            >
                Filter
            </button>

        </form>

    </div>


    @if($schedules->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>

                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Faculty</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($schedules as $schedule)

                        <tr>

                            <td>

                                <div class="table-primary">

                                    {{ $schedule->day }}

                                    ·

                                    {{ $schedule->start_time?->format('g:i A') }}

                                    –

                                    {{ $schedule->end_time?->format('g:i A') }}

                                </div>

                            </td>


                            <td>

                                <div class="table-primary">
                                    {{ $schedule->room->room_name ?? '—' }}
                                </div>

                                <div class="table-secondary">

                                    {{ $schedule->room->floor->building->building_name ?? '—' }}

                                </div>

                            </td>


                            <td>

                                {{ $schedule->faculty->full_name ?? '—' }}

                            </td>


                            <td>

                                <span class="code-badge">

                                    {{ $schedule->subject->subject_code ?? '—' }}

                                </span>

                            </td>


                            <td>

                                {{ $schedule->section->section_name ?? '—' }}

                            </td>


                            <td>

                                @if($schedule->status === 'active')

                                    <span class="room-status status-available">
                                        Active
                                    </span>

                                @else

                                    <span class="room-status status-maintenance">
                                        Inactive
                                    </span>

                                @endif

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('schedules.show', $schedule) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('schedules.edit', $schedule) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('schedules.destroy', $schedule) }}"
                                        onsubmit="return confirm('Delete this class session?');"
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

            {{ $schedules->links() }}

        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                ◷
            </div>

            <h3>No class sessions found</h3>

            <p>
                Create a schedule by assigning a room, faculty member,
                subject, section, day, and time.
            </p>

            <a
                href="{{ route('schedules.create') }}"
                class="btn-primary"
            >
                + Add Class Session
            </a>

        </div>

    @endif

</div>

@endsection