@extends('layouts.admin')

@section('title', 'Subject Details')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>{{ $subject->subject_code }}</h1>

        <p>
            {{ $subject->subject_name }}
        </p>

    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('subjects.edit', $subject) }}"
            class="btn-primary"
        >
            Edit Subject
        </a>

        <a
            href="{{ route('subjects.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">


    {{-- SUBJECT INFORMATION --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    SUBJECT INFORMATION
                </span>

                <h2>Details</h2>

            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">

                <span>Subject Code</span>

                <strong>
                    {{ $subject->subject_code }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Subject Name</span>

                <strong>
                    {{ $subject->subject_name }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Class Sessions</span>

                <strong>
                    {{ $subject->classSessions->count() }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Description</span>

                <strong>
                    {{ $subject->description ?: 'No description provided.' }}
                </strong>

            </div>

        </div>

    </div>


    {{-- CLASS SESSIONS --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    SCHEDULE
                </span>

                <h2>Class Sessions</h2>

            </div>

            <span class="count-badge">
                {{ $subject->classSessions->count() }}
            </span>

        </div>


        @if($subject->classSessions->count())

            <div class="floor-list">

                @foreach($subject->classSessions as $session)

                    <div class="floor-item">

                        <div class="floor-icon">
                            ◷
                        </div>

                        <div class="floor-info">

                            <strong>

                                {{ $session->day }}

                                ·

                                {{ $session->start_time?->format('g:i A') }}

                                –

                                {{ $session->end_time?->format('g:i A') }}

                            </strong>

                            <span>

                                {{ $session->faculty->full_name ?? 'No Faculty' }}

                                ·

                                {{ $session->section->section_name ?? 'No Section' }}

                                ·

                                {{ $session->room->room_name ?? 'No Room' }}

                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">

                <strong>No class sessions</strong>

                <span>
                    This subject has not been assigned to a schedule.
                </span>

            </div>

        @endif

    </div>

</div>

@endsection