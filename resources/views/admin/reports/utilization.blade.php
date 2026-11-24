@extends('layouts.admin')

@section('title', 'Classroom Utilization Report')

@section('content')

@php
    $hours = fn (int $minutes): string => intdiv($minutes, 60).'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
    $totalMinutes = (int) $minutesByRoom->sum('minutes');
@endphp

<div class="page-header">
    <div>
        <span class="page-eyebrow">REPORTS</span>
        <h1>Classroom Utilization Report</h1>
        <p>{{ $filters->from->format('M j, Y') }} to {{ $filters->to->format('M j, Y') }}</p>
    </div>
</div>

@include('admin.reports.partials.filters', ['route' => 'reports.utilization'])

<div class="dashboard-stats">
    <div class="dashboard-stat-card"><div class="stat-content"><span>Sessions</span><strong>{{ $sessions->total() }}</strong></div></div>
    <div class="dashboard-stat-card"><div class="stat-content"><span>Total Time Used</span><strong>{{ $hours($totalMinutes) }}</strong></div></div>
    <div class="dashboard-stat-card"><div class="stat-content"><span>Rooms Used</span><strong>{{ $minutesByRoom->count() }}</strong></div></div>
    <div class="dashboard-stat-card"><div class="stat-content"><span>Average Session</span><strong>{{ $hours($sessions->total() ? intdiv($totalMinutes, $sessions->total()) : 0) }}</strong></div></div>
</div>

@if($minutesByRoom->isNotEmpty())
    <div class="content-card">
        <div class="table-toolbar"><div><h2>By Room</h2><span class="table-count">Most used first</span></div></div>
        <div class="table-responsive">
            <table class="roomlink-table">
                <thead><tr><th>Room</th><th>Building</th><th>Sessions</th><th>Time Used</th></tr></thead>
                <tbody>
                    @foreach($minutesByRoom as $roomId => $usage)
                        <tr>
                            <td class="table-primary">{{ $roomsById[$roomId]?->room_name }}</td>
                            <td>{{ $roomsById[$roomId]?->floor?->building?->building_name }}</td>
                            <td>{{ $usage['sessions'] }}</td>
                            <td>{{ $hours((int) $usage['minutes']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="content-card">

    <div class="table-toolbar"><div><h2>Sessions</h2><span class="table-count">{{ $sessions->total() }} {{ Str::plural('session', $sessions->total()) }}</span></div></div>

    @if($sessions->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="chart" /></div>
            <h3>No sessions in this period</h3>
            <p>Sessions appear here once faculty start classes in the rooms you chose.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="roomlink-table">
                <thead><tr><th>Classroom</th><th>Faculty</th><th>Subject</th><th>Section</th><th>Date and Time</th><th>Duration</th></tr></thead>
                <tbody>
                    @foreach($sessions as $session)
                        <tr>
                            <td>
                                <div class="table-primary">{{ $session->room->room_name }}</div>
                                <div class="table-secondary">{{ $session->room->floor?->building?->building_name }} · {{ $session->room->floor?->building?->campus?->campus_name }}</div>
                            </td>
                            <td>{{ $session->faculty->full_name }}</td>
                            <td>
                                <div class="table-primary">{{ $session->classSession?->subject?->subject_code ?? 'Temporary session' }}</div>
                                <div class="table-secondary">{{ $session->classSession?->subject?->subject_name }}</div>
                            </td>
                            <td>{{ $session->classSession?->section?->section_name ?? '—' }}</td>
                            <td class="nowrap">
                                <div class="table-primary">{{ $session->started_at->format('M j, Y') }}</div>
                                <div class="table-secondary">{{ $session->started_at->format('g:i A') }} – {{ $session->ended_at?->format('g:i A') ?? 'now' }}</div>
                            </td>
                            <td class="nowrap">
                                {{ $hours($session->durationMinutes()) }}
                                @if($session->isActive())
                                    <div class="table-secondary">In session</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="table-pagination">{{ $sessions->links() }}</div>
        @endif
    @endif

</div>

@endsection
