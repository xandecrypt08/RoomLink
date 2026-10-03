@extends('layouts.admin')

@section('title', 'Faculty Details')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>{{ $faculty->full_name }}</h1>

        <p>
            Faculty information and assigned class sessions.
        </p>

    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('faculties.edit', $faculty) }}"
            class="btn-primary"
        >
            Edit Faculty
        </a>

        <a
            href="{{ route('faculties.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">


    {{-- FACULTY INFORMATION --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    FACULTY INFORMATION
                </span>

                <h2>Details</h2>

            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">

                <span>Full Name</span>

                <strong>
                    {{ $faculty->full_name }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Employee ID</span>

                <strong>
                    {{ $faculty->employee_id }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Email</span>

                <strong>
                    {{ $faculty->email ?: '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Department</span>

                <strong>
                    {{ $faculty->department ?: '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Class Sessions</span>

                <strong>
                    {{ $faculty->classSessions->count() }}
                </strong>

            </div>

        </div>

    </div>


    {{-- CLASS SESSIONS --}}

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    TEACHING SCHEDULE
                </span>

                <h2>Class Sessions</h2>

            </div>

            <span class="count-badge">
                {{ $faculty->classSessions->count() }}
            </span>

        </div>


        @if($faculty->classSessions->count())

            <div class="floor-list">

                @foreach($faculty->classSessions as $session)

                    <div class="floor-item">

                        <div class="floor-icon">
                            ◷
                        </div>

                        <div class="floor-info">

                            <strong>

                                {{ $session->subject->subject_code ?? 'No Subject' }}

                                — {{ $session->section->section_name ?? 'No Section' }}

                            </strong>

                            <span>

                                {{ $session->day }}

                                ·

                                {{ $session->start_time?->format('g:i A') }}

                                –

                                {{ $session->end_time?->format('g:i A') }}

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
                    This faculty member has no assigned schedules yet.
                </span>

            </div>

        @endif

    </div>

</div>

@endsection