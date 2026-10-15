@extends('layouts.app')

@section('title', 'Class Not Held')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM SESSIONS</span>

        <h1>Mark Class as Not Held</h1>

        <p>
            Record that today's class did not take place. The room is released for other classes.
        </p>

    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
            ← Back to Dashboard
        </a>
    </div>

</div>


@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<div class="content-card detail-card">

    <div class="detail-card-header">
        <div>
            <span class="page-eyebrow">TODAY'S CLASS</span>
            <h2>
                {{ $schedule->subject->subject_code }}
                ·
                {{ $schedule->section->section_name }}
            </h2>
        </div>
    </div>

    <div class="detail-list">

        <div class="detail-item">
            <span>Subject</span>
            <strong>{{ $schedule->subject->subject_name }}</strong>
        </div>

        <div class="detail-item">
            <span>Schedule</span>
            <strong>
                {{ now()->format('l, M j') }},
                {{ $schedule->start_time->format('g:i A') }}
                –
                {{ $schedule->end_time->format('g:i A') }}
            </strong>
        </div>

        <div class="detail-item">
            <span>Room</span>
            <strong>
                {{ $schedule->room->room_name }}
                ·
                {{ $schedule->room->floor?->building?->building_name ?? '—' }}
            </strong>
        </div>

    </div>

</div>


<div class="content-card form-card">

    <form action="{{ route('faculty.not-held.store', $schedule) }}" method="POST">
        @csrf

        <div class="form-grid">

            <div class="form-group form-group-full">
                <label for="reason">
                    Reason <span class="required">*</span>
                </label>

                <textarea
                    name="reason"
                    id="reason"
                    rows="3"
                    maxlength="500"
                    required
                    placeholder="e.g. Faculty on official leave; class moved to an online session."
                >{{ old('reason') }}</textarea>
            </div>

        </div>

        <div class="form-actions">
            <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-delete">
                Mark as Not Held
            </button>
        </div>

    </form>

</div>

@endsection
