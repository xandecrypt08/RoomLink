@extends('layouts.admin')

@section('title', 'Floor Details')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Floor {{ $floor->floor_number }}</h1>

        <p>
            Floor information and rooms assigned to this floor.
        </p>
    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('floors.edit', $floor) }}"
            class="btn-primary"
        >
            Edit Floor
        </a>

        <a
            href="{{ route('floors.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">

    {{-- FLOOR INFORMATION --}}
    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>
                <span class="panel-eyebrow">
                    FLOOR INFORMATION
                </span>

                <h2>Details</h2>
            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">

                <span>Floor Number</span>

                <strong>
                    Floor {{ $floor->floor_number }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Building</span>

                <strong>
                    {{ $floor->building->building_name ?? '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Building Code</span>

                <strong>
                    {{ $floor->building->building_code ?? '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Campus</span>

                <strong>
                    {{ $floor->building->campus->campus_name ?? '—' }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Rooms</span>

                <strong>
                    {{ $floor->rooms->count() }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Description</span>

                <strong>
                    {{ $floor->description ?: 'No description provided.' }}
                </strong>

            </div>

        </div>

    </div>


    {{-- ROOMS --}}
    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    CLASSROOMS
                </span>

                <h2>Rooms on this Floor</h2>

            </div>

            <span class="count-badge">
                {{ $floor->rooms->count() }}
            </span>

        </div>


        @if($floor->rooms->count())

            <div class="floor-list">

                @foreach($floor->rooms as $room)

                    <div class="floor-item">

                        <div class="floor-icon">
                            □
                        </div>

                        <div class="floor-info">

                            <strong>
                                {{ $room->room_name }}
                            </strong>

                            <span>
                                Status:
                                {{ ucfirst($room->status ?? 'available') }}
                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">

                <strong>No rooms registered</strong>

                <span>
                    Rooms assigned to this floor will appear here.
                </span>

            </div>

        @endif

    </div>

</div>

@endsection