@extends('layouts.app')

@section('title', 'Faculty Locator')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">FACULTY LOCATOR</span>

        <h1>Faculty Locator</h1>

        <p>
            Faculty members currently holding a class. A faculty member is
            only listed while their classroom session is running.
        </p>

    </div>

</div>


<div class="dashboard-stats">

    <div class="dashboard-stat-card">
        <div class="stat-icon stat-icon-occupied">●</div>
        <div class="stat-content">
            <span>In Class Now</span>
            <strong>{{ $locatedSessions->count() }}</strong>
            <small>Faculty with an active session</small>
        </div>
    </div>

</div>


<div class="content-card">

    <div class="table-toolbar">
        <div>
            <h2>Active Faculty Locations</h2>
            <span class="table-count">
                As of {{ now()->format('l, g:i A') }}
            </span>
        </div>
    </div>

    @if($locatedSessions->isEmpty())

        <div class="empty-state">
            <div class="empty-state-icon">⌖</div>
            <h3>Nobody in class</h3>
            <p>No faculty members are holding a class right now.</p>
        </div>

    @else

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Classroom</th>
                        <th>Schedule</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($locatedSessions as $session)

                        <tr>

                            <td>
                                <div class="table-primary">{{ $session->faculty->full_name }}</div>
                                @if($session->faculty->department)
                                    <div class="table-secondary">{{ $session->faculty->department }}</div>
                                @endif
                            </td>

                            @if($session->classSession)

                                <td>
                                    <div class="table-primary">{{ $session->classSession->subject->subject_code }}</div>
                                    <div class="table-secondary">{{ $session->classSession->subject->subject_name }}</div>
                                </td>

                                <td>
                                    {{ $session->classSession->section->section_name }}
                                </td>

                            @else

                                <td colspan="2">
                                    <span class="room-status status-neutral">Temporary Session</span>
                                </td>

                            @endif

                            <td>
                                <div class="table-primary">{{ $session->room->room_name }}</div>
                                <div class="table-secondary">
                                    {{ $session->room->floor?->building?->building_name }}
                                    @if($session->room->floor)
                                        · Floor {{ $session->room->floor->floor_number }}
                                    @endif
                                    @if($session->room->floor?->building?->campus)
                                        · {{ $session->room->floor->building->campus->campus_name }}
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="table-primary">
                                    @if($session->classSession)
                                        {{ $session->classSession->start_time->format('g:i A') }}
                                        -
                                        {{ $session->classSession->end_time->format('g:i A') }}
                                    @else
                                        Since {{ $session->started_at->format('g:i A') }}
                                    @endif
                                </div>
                                <span class="room-status status-occupied">In Session</span>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif

</div>

@endsection
