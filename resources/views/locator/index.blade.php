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


<div class="dashboard-grid">

    <div class="dashboard-card">

        <div class="card-label">
            IN CLASS NOW
        </div>

        <h2>
            {{ $locatedSessions->count() }}
        </h2>

        <p>
            Faculty members with an active classroom session.
        </p>

    </div>

</div>


<div class="content-card">

    <div class="section-header">

        <div>

            <h2>Active Faculty Locations</h2>

            <p>
                As of {{ now()->format('l, g:i A') }}.
            </p>

        </div>

    </div>


    <div class="table-container">

        <table class="data-table">

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

                @forelse($locatedSessions as $session)

                    <tr>

                        <td>

                            <strong>
                                {{ $session->faculty->full_name }}
                            </strong>

                            @if($session->faculty->department)
                                <br>

                                <small>
                                    {{ $session->faculty->department }}
                                </small>
                            @endif

                        </td>

                        @if($session->classSession)

                            <td>

                                <strong>
                                    {{ $session->classSession->subject->subject_code }}
                                </strong>

                                <br>

                                <small>
                                    {{ $session->classSession->subject->subject_name }}
                                </small>

                            </td>

                            <td>
                                {{ $session->classSession->section->section_name }}
                            </td>

                        @else

                            <td colspan="2">
                                <span class="status-badge">
                                    Temporary Session
                                </span>
                            </td>

                        @endif

                        <td>

                            <strong>
                                {{ $session->room->room_name }}
                            </strong>

                            <br>

                            <small>
                                {{ $session->room->floor?->building?->building_name }}
                                @if($session->room->floor)
                                    · Floor {{ $session->room->floor->floor_number }}
                                @endif
                                @if($session->room->floor?->building?->campus)
                                    · {{ $session->room->floor->building->campus->campus_name }}
                                @endif
                            </small>

                        </td>

                        <td>

                            @if($session->classSession)
                                {{ $session->classSession->start_time->format('g:i A') }}
                                -
                                {{ $session->classSession->end_time->format('g:i A') }}
                            @else
                                Since {{ $session->started_at->format('g:i A') }}
                            @endif

                            <br>

                            <span class="status-badge status-active">
                                In Session
                            </span>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="empty-state">
                            No faculty members are holding a class right now.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection
