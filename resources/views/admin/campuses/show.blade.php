@extends('layouts.admin')

@section('title', 'Campus Details')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>{{ $campus->campus_name }}</h1>

        <p>
            Campus information and its buildings.
        </p>
    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('campuses.edit', $campus) }}"
            class="btn-primary"
        >
            Edit Campus
        </a>

        <a
            href="{{ route('campuses.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">

    {{-- CAMPUS INFORMATION --}}
    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>
                <span class="panel-eyebrow">
                    CAMPUS INFORMATION
                </span>

                <h2>Details</h2>
            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">
                <span>Campus Name</span>
                <strong>{{ $campus->campus_name }}</strong>
            </div>

            <div class="detail-item">
                <span>Campus Code</span>
                <strong>{{ $campus->campus_code ?: '—' }}</strong>
            </div>

            <div class="detail-item">
                <span>Address</span>
                <strong>{{ $campus->address ?: '—' }}</strong>
            </div>

            <div class="detail-item">
                <span>Description</span>
                <strong>{{ $campus->description ?: 'No description provided.' }}</strong>
            </div>

        </div>

    </div>


    {{-- BUILDINGS --}}
    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>
                <span class="panel-eyebrow">
                    CAMPUS STRUCTURE
                </span>

                <h2>Buildings</h2>
            </div>

            <span class="count-badge">
                {{ $campus->buildings->count() }}
            </span>

        </div>


        @if($campus->buildings->count())

            <div class="floor-list">

                @foreach($campus->buildings as $building)

                    <div class="floor-item">

                        <div class="floor-icon">
                            ▤
                        </div>

                        <div class="floor-info">

                            <strong>
                                {{ $building->building_name }}
                            </strong>

                            <span>
                                {{ $building->building_code ?: 'No code' }}
                                · {{ $building->number_of_floors }} floor{{ $building->number_of_floors != 1 ? 's' : '' }}
                            </span>

                        </div>

                        <a
                            href="{{ route('buildings.show', $building) }}"
                            class="btn-secondary"
                        >
                            View
                        </a>

                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">
                <strong>No buildings yet</strong>
                <span>Buildings assigned to this campus will appear here.</span>
            </div>

        @endif

    </div>

</div>

@endsection
