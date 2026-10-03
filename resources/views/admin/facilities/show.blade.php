@extends('layouts.admin')

@section('title', 'Facility Details')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>{{ $facility->facility_name }}</h1>

        <p>
            Facility information and classrooms using this facility.
        </p>

    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('facilities.edit', $facility) }}"
            class="btn-primary"
        >
            Edit Facility
        </a>

        <a
            href="{{ route('facilities.index') }}"
            class="btn-secondary"
        >
            ← Back
        </a>

    </div>

</div>


<div class="details-grid">

    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    FACILITY INFORMATION
                </span>

                <h2>Details</h2>

            </div>

        </div>


        <div class="detail-list">

            <div class="detail-item">

                <span>Facility</span>

                <strong>
                    {{ $facility->facility_name }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Rooms Using Facility</span>

                <strong>
                    {{ $facility->rooms->count() }}
                </strong>

            </div>


            <div class="detail-item">

                <span>Created</span>

                <strong>
                    {{ $facility->created_at?->format('M d, Y') }}
                </strong>

            </div>

        </div>

    </div>


    <div class="content-card detail-card">

        <div class="detail-card-header">

            <div>

                <span class="panel-eyebrow">
                    CLASSROOMS
                </span>

                <h2>Assigned Rooms</h2>

            </div>

            <span class="count-badge">
                {{ $facility->rooms->count() }}
            </span>

        </div>


        @if($facility->rooms->count())

            <div class="floor-list">

                @foreach($facility->rooms as $room)

                    <div class="floor-item">

                        <div class="floor-icon">
                            □
                        </div>

                        <div class="floor-info">

                            <strong>
                                {{ $room->room_name }}
                            </strong>

                            <span>

                                {{ $room->floor->building->building_name ?? '—' }}

                                ·

                                Floor {{ $room->floor->floor_number ?? '—' }}

                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="small-empty-state">

                <strong>No rooms assigned</strong>

                <span>
                    This facility is not currently assigned to a room.
                </span>

            </div>

        @endif

    </div>

</div>

@endsection