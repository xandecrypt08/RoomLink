@extends('layouts.app')

@section('title', 'Temporary Classroom Requests')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM SESSIONS</span>

        <h1>Temporary Classroom Requests</h1>

        <p>
            Request another classroom when your assigned room cannot be used.
        </p>

    </div>

    <div class="page-header-actions">

        <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
            ← Back to Dashboard
        </a>

        <a href="{{ route('faculty.temporary-requests.create') }}" class="btn btn-primary">
            + Scan Room
        </a>

    </div>

</div>


@if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif


{{-- Incoming Requests --}}

<div class="content-card">

    <div class="table-toolbar">

        <div>

            <h2>Requests for Your Classes</h2>

            <span class="table-count">
                Faculty asking to use a room while you are scheduled in it
            </span>

        </div>

    </div>

    @if($incomingRequests->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>
                        <th>Requested</th>
                        <th>Requester</th>
                        <th>Room</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($incomingRequests as $temporaryRequest)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $temporaryRequest->created_at->format('M d, Y') }}
                                </div>

                                <div class="table-secondary">
                                    {{ $temporaryRequest->created_at->format('g:i A') }}
                                </div>

                            </td>

                            <td>

                                <div class="table-primary">
                                    {{ $temporaryRequest->requester->full_name }}

                                    @if(in_array($temporaryRequest->id, $newRequestIds))
                                        <span class="status-badge status-available">New</span>
                                    @endif
                                </div>

                                @if($temporaryRequest->requesterClassSession)
                                    <div class="table-secondary">
                                        {{ $temporaryRequest->requesterClassSession->subject->subject_code }}
                                        ·
                                        {{ $temporaryRequest->requesterClassSession->section->section_name }}
                                    </div>
                                @endif

                            </td>

                            <td>

                                <div class="table-primary">
                                    {{ $temporaryRequest->room->room_name }}
                                </div>

                                <div class="table-secondary">
                                    {{ $temporaryRequest->room->floor->building->building_name ?? '—' }}
                                </div>

                            </td>

                            <td>
                                {{ $temporaryRequest->reason }}
                            </td>

                            <td>
                                @include('faculty.temporary-requests.partials.status-badge', [
                                    'status' => $temporaryRequest->status,
                                ])
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="empty-state">
            No one has requested your classrooms.
        </div>

    @endif

</div>


{{-- My Requests --}}

<div class="content-card">

    <div class="table-toolbar">

        <div>

            <h2>My Requests</h2>

            <span class="table-count">
                {{ $myRequests->count() }}
                request{{ $myRequests->count() != 1 ? 's' : '' }}
            </span>

        </div>

    </div>

    @if($myRequests->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>
                        <th>Requested</th>
                        <th>Room</th>
                        <th>For Class</th>
                        <th>Scheduled Faculty</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($myRequests as $temporaryRequest)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $temporaryRequest->created_at->format('M d, Y') }}
                                </div>

                                <div class="table-secondary">
                                    {{ $temporaryRequest->created_at->format('g:i A') }}
                                </div>

                            </td>

                            <td>

                                <div class="table-primary">
                                    {{ $temporaryRequest->room->room_name }}
                                </div>

                                <div class="table-secondary">
                                    {{ $temporaryRequest->room->floor->building->building_name ?? '—' }}
                                </div>

                            </td>

                            <td>
                                {{ $temporaryRequest->requesterClassSession->subject->subject_code ?? '—' }}
                            </td>

                            <td>
                                {{ $temporaryRequest->scheduledFaculty->full_name ?? 'No class scheduled' }}
                            </td>

                            <td>
                                {{ $temporaryRequest->reason }}
                            </td>

                            <td>
                                @include('faculty.temporary-requests.partials.status-badge', [
                                    'status' => $temporaryRequest->status,
                                ])
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="empty-state">
            You have not requested any temporary classrooms yet.
        </div>

    @endif

</div>

@endsection
