@extends('layouts.admin')

@section('title', 'Building Details')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>{{ $building->building_name }}</h1>

        <p>
            Building information and floor structure.
        </p>
    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('buildings.edit', $building) }}"
            class="btn-primary"
        >
            Edit Building
        </a>

        <a
            href="{{ route('buildings.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">

    {{-- BUILDING INFORMATION --}}
    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>
                <span class="panel-eyebrow">
                    BUILDING INFORMATION
                </span>

                <h2>Details</h2>
            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">
                <span>Building Name</span>
                <strong>
                    {{ $building->building_name }}
                </strong>
            </div>


            <div class="detail-item">
                <span>Building Code</span>

                <strong>
                    {{ $building->building_code ?: '—' }}
                </strong>
            </div>


            <div class="detail-item">
                <span>Campus</span>

                <strong>
                    {{ $building->campus->campus_name ?? '—' }}
                </strong>
            </div>


            <div class="detail-item">
                <span>Number of Floors</span>

                <strong>
                    {{ $building->number_of_floors }}
                </strong>
            </div>


            <div class="detail-item">
                <span>Description</span>

                <strong>
                    {{ $building->description ?: 'No description provided.' }}
                </strong>
            </div>

        </div>

    </div>


    {{-- FLOORS --}}
    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>
                <span class="panel-eyebrow">
                    BUILDING STRUCTURE
                </span>

                <h2>Floors</h2>
            </div>

            <span class="count-badge">
                {{ $building->floors->count() }}
            </span>

        </div>


        @if($building->floors->count())

            <div class="floor-list">

                @foreach($building->floors as $floor)

                    <div class="floor-item">

                        <div class="floor-icon">
                            {{ $floor->floor_number }}
                        </div>

                        <div class="floor-info">

                            <strong>
                                Floor {{ $floor->floor_number }}
                            </strong>

                            <span>
                                {{ $floor->description ?: 'No description' }}
                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">
                <strong>No floors registered</strong>

                <span>
                    Floors can be added from Floor Management.
                </span>
            </div>

        @endif

    </div>

</div>

@endsection