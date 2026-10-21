@extends('layouts.app')

@section('title', 'My Schedule')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">OVERVIEW</span>

        <h1>My Schedule</h1>

        <p>
            Your classes for the week. Upload your teaching load to add them; the classes are assigned to you automatically.
        </p>

    </div>

    @if($faculty)
        <div class="page-header-actions">
            <a href="{{ route('faculty.schedule.import.create') }}" class="btn btn-primary">
                Upload My Teaching Load
            </a>
        </div>
    @endif

</div>


@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif


<div class="content-card">

    @if($faculty === null)

        <div class="small-empty-state">
            <strong>Your account isn't linked to a faculty record yet</strong>
            <span>Ask the administrator to add you under Faculty with the same employee ID as your account.</span>
        </div>

    @elseif($classesByDay->isEmpty())

        <div class="small-empty-state">
            <strong>No classes yet</strong>
            <span>Upload your teaching load (PDF, Excel, Word, or a photo of your class list) to add your classes.</span>
        </div>

    @else

        <div class="table-toolbar">
            <div>
                <h2>Weekly Classes</h2>
                <span class="table-count">
                    {{ $classesByDay->flatten()->count() }}
                    {{ \Illuminate\Support\Str::plural('class', $classesByDay->flatten()->count()) }}
                </span>
            </div>
        </div>

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Room</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($classesByDay as $day => $classes)

                        @foreach($classes as $class)

                            <tr>

                                <td class="nowrap">
                                    @if($loop->first)
                                        <div class="table-primary">{{ $dayNames[$day] ?? $day }}</div>
                                    @endif
                                </td>

                                <td class="nowrap">
                                    {{ $class->start_time?->format('g:i A') }} – {{ $class->end_time?->format('g:i A') }}
                                </td>

                                <td>
                                    <div class="table-primary">{{ $class->subject->subject_code ?? '—' }}</div>
                                    <div class="table-secondary">{{ $class->subject->subject_name ?? '' }}</div>
                                </td>

                                <td>{{ $class->section->section_name ?? '—' }}</td>

                                <td>
                                    <div class="table-primary">{{ $class->room->room_name ?? '—' }}</div>
                                    <div class="table-secondary">{{ $class->room->floor->building->building_name ?? '' }}</div>
                                </td>

                            </tr>

                        @endforeach

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif

</div>

@endsection
