@extends('layouts.admin')

@section('title', 'Section Details')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>{{ $section->section_name }}</h1>

        <p>
            Section information and assigned class sessions.
        </p>

    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('sections.edit', $section) }}"
            class="btn-primary"
        >
            Edit Section
        </a>

        <a
            href="{{ route('sections.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">


    {{-- SECTION INFORMATION --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    SECTION INFORMATION
                </span>

                <h2>Details</h2>

            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">

                <span>Section Name</span>

                <strong>
                    {{ $section->section_name }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Class Sessions</span>

                <strong>
                    {{ $section->classSessions->count() }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Description</span>

                <strong>
                    {{ $section->description ?: 'No description provided.' }}
                </strong>

            </div>

        </div>

    </div>


    {{-- CLASS SESSIONS --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    CLASS SCHEDULE
                </span>

                <h2>Class Sessions</h2>

            </div>

            <span class="count-badge">
                {{ $section->classSessions->count() }}
            </span>

        </div>


        @if($section->classSessions->count())

            <div class="floor-list">

                @foreach($section->classSessions as $session)

                    <div class="floor-item">

                        <div class="floor-icon">
                            ◷
                        </div>

                        <div class="floor-info">

                            <strong>

                                {{ $session->subject->subject_code ?? 'No Subject' }}

                                ·

                                {{ $session->day }}

                            </strong>

                            <span>

                                {{ $session->start_time?->format('g:i A') }}

                                –

                                {{ $session->end_time?->format('g:i A') }}

                                ·

                                {{ $session->faculty->full_name ?? 'No Faculty' }}

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
                    This section has not been assigned to a schedule.
                </span>

            </div>

        @endif

    </div>

</div>

@endsection