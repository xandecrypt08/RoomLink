@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

{{-- PAGE HEADER --}}
<div class="dashboard-header">

    <div>
        <span class="dashboard-eyebrow">ROOMLINK ADMINISTRATION</span>

        <h1>Dashboard</h1>

        <p>
            Overview of classroom spaces, schedules, and room operations.
        </p>
    </div>

    <x-term-picker />

</div>


@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<x-term-notice />


{{-- SUMMARY CARDS --}}
<div class="dashboard-stats">

    <div class="dashboard-stat-card">

        <div class="stat-icon campus-icon">
            <x-icon name="landmark" />
        </div>

        <div class="stat-content">
            <span>Total Campuses</span>
            <strong>{{ $totalCampuses }}</strong>
            <small>Registered campuses</small>
        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon building-icon">
            <x-icon name="building" />
        </div>

        <div class="stat-content">
            <span>Total Buildings</span>
            <strong>{{ $totalBuildings }}</strong>
            <small>Registered buildings</small>
        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon floor-icon">
            <x-icon name="layers" />
        </div>

        <div class="stat-content">
            <span>Total Floors</span>
            <strong>{{ $totalFloors }}</strong>
            <small>Registered floors</small>
        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon room-icon">
            <x-icon name="door" />
        </div>

        <div class="stat-content">
            <span>Total Rooms</span>
            <strong>{{ $totalRooms }}</strong>
            <small>Registered classrooms</small>
        </div>

    </div>

</div>


{{-- LIVE SUMMARY --}}
<div data-live-dashboard data-live-url="{{ route('admin.dashboard.live') }}">
    @include('admin.dashboard.live')
</div>


{{-- RECENT CLASS SESSIONS --}}
<div class="dashboard-panel sessions-panel">

    <div class="panel-header">

        <div>
            <span class="panel-eyebrow">SCHEDULES</span>
            <h2>Class Schedules</h2>
            <p class="panel-term">{{ \App\Support\SelectedTerm::get()->label }}</p>
        </div>

        <a href="{{ route('schedules.index') }}" class="panel-link">
            View Schedules →
        </a>

    </div>


    @if($recentSessions->count())

        <div class="session-list">

            @foreach($recentSessions as $session)

                <div class="session-row">

                    <div class="session-room">
                        <strong>{{ $session->room->room_name ?? 'N/A' }}</strong>

                        <span>
                            {{ $session->room->floor->building->building_name ?? 'N/A' }}
                        </span>
                    </div>


                    <div class="session-subject">

                        <strong>
                            {{ $session->subject->subject_code ?? 'N/A' }}
                        </strong>

                        <span>
                            {{ $session->subject->subject_name ?? 'No subject name' }}
                        </span>

                    </div>


                    <div class="session-faculty">

                        <strong>
                            {{ $session->faculty->full_name ?? 'N/A' }}
                        </strong>

                        <span>
                            {{ $session->section->section_name ?? 'No section' }}
                        </span>

                    </div>


                    <div class="session-time">

                        <strong>
                            {{ $session->day_name }}
                        </strong>

                        <span>
                            {{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }}
                            –
                            {{ \Carbon\Carbon::parse($session->end_time)->format('g:i A') }}
                        </span>

                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="dashboard-empty">

            <div class="empty-icon"><x-icon name="calendar" /></div>

            <strong>No class sessions yet</strong>

            <p>
                Create a class schedule to see classroom activity here.
            </p>

            <a href="{{ route('schedules.create') }}" class="empty-action">
                Create Schedule
            </a>

        </div>

    @endif

</div>


@endsection


@push('scripts')
<script src="{{ \App\Support\Asset::url('js/dashboard.js') }}"></script>
@endpush
