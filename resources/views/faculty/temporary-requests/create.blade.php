@extends('layouts.app')

@section('title', 'Request Temporary Classroom')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM SESSIONS</span>

        <h1>Request Temporary Classroom</h1>

        <p>
            Scan the room you want to use, then submit your request.
        </p>

    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.temporary-requests.index') }}" class="btn btn-secondary">
            ← Back to Requests
        </a>
    </div>

</div>


@if ($errors->any())
    <div class="alert alert-error">
        <strong>Please check the following:</strong>

        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


{{-- Step 1: Scan Room --}}

<div class="content-card form-card">

    <div class="form-card-header">
        <div>
            <h2>1. Scan Room</h2>
            <p>Enter the code printed under the room's QR code.</p>
        </div>
    </div>

    <form action="{{ route('faculty.temporary-requests.create') }}" method="GET">

        <div class="form-grid">

            <div class="form-group form-group-full">
                <label for="code">
                    Room QR Code <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="code"
                    id="code"
                    value="{{ $code }}"
                    placeholder="e.g. ROOM-AB12CD34EF"
                    required
                >
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-secondary">
                Find Room
            </button>
        </div>

    </form>

</div>


@if ($code !== '' && ! $room)

    <div class="alert alert-error">
        No classroom matches the code "{{ $code }}". Check the code and try again.
    </div>

@endif


@if ($room)

    {{-- Room Details --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">
            <div>
                <span class="page-eyebrow">SCANNED ROOM</span>
                <h2>{{ $room->room_name }}</h2>
            </div>

            <span @class([
                'status-badge',
                'status-available' => $room->status === 'available',
                'status-occupied' => $room->status === 'occupied',
                'status-maintenance' => $room->status === 'maintenance',
            ])>
                {{ ucfirst($room->status) }}
            </span>
        </div>

        <div class="detail-list">

            <div class="detail-item">
                <span>Location</span>
                <strong>
                    {{ $room->floor->building->campus->campus_name ?? '—' }}
                    ·
                    {{ $room->floor->building->building_name ?? '—' }}
                    ·
                    Floor {{ $room->floor->floor_number ?? '—' }}
                </strong>
            </div>

            <div class="detail-item">
                <span>Scheduled Faculty</span>
                <strong>
                    {{ $scheduledSession->faculty->full_name ?? 'No class scheduled right now' }}
                </strong>
            </div>

            @if ($scheduledSession)

                <div class="detail-item">
                    <span>Scheduled Class</span>
                    <strong>
                        {{ $scheduledSession->subject->subject_code }}
                        ·
                        {{ $scheduledSession->section->section_name }}
                        ·
                        {{ $scheduledSession->start_time->format('g:i A') }}
                        –
                        {{ $scheduledSession->end_time->format('g:i A') }}
                    </strong>
                </div>

            @endif

        </div>

    </div>


    {{-- Step 2: Submit Request --}}

    @if ($room->status === 'maintenance')

        <div class="alert alert-error">
            This room is under maintenance and cannot be requested.
        </div>

    @else

        <div class="content-card form-card">

            <div class="form-card-header">
                <div>
                    <h2>2. Submit Request</h2>
                    <p>
                        @if ($scheduledSession)
                            {{ $scheduledSession->faculty->full_name }} will be notified of your request.
                        @else
                            Your request will be recorded as pending.
                        @endif
                    </p>
                </div>
            </div>

            <form action="{{ route('faculty.temporary-requests.store') }}" method="POST">
                @csrf

                <input type="hidden" name="room_id" value="{{ $room->id }}">

                <div class="form-grid">

                    <div class="form-group form-group-full">
                        <label for="requester_class_session_id">
                            For Class
                        </label>

                        <select name="requester_class_session_id" id="requester_class_session_id">
                            <option value="">Not linked to a class</option>

                            @foreach ($mySessionsToday as $session)
                                <option
                                    value="{{ $session->id }}"
                                    {{ old('requester_class_session_id') == $session->id ? 'selected' : '' }}
                                >
                                    {{ $session->start_time->format('g:i A') }}
                                    –
                                    {{ $session->end_time->format('g:i A') }}
                                    ·
                                    {{ $session->subject->subject_code }}
                                    ({{ $session->section->section_name }})
                                    · assigned to {{ $session->room->room_name }}
                                </option>
                            @endforeach
                        </select>

                        <small class="form-note">
                            The class of yours that cannot use its assigned room.
                        </small>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="reason">
                            Reason <span class="required">*</span>
                        </label>

                        <textarea
                            name="reason"
                            id="reason"
                            rows="4"
                            maxlength="500"
                            placeholder="Why can't your assigned room be used?"
                            required
                        >{{ old('reason') }}</textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="{{ route('faculty.temporary-requests.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Submit Request
                    </button>
                </div>

            </form>

        </div>

    @endif

@endif

@endsection
