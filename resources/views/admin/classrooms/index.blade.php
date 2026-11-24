@extends('layouts.admin')

@section('title', 'Campuses')

@section('content')

<div class="classroom-page" data-record-page>

<div class="page-header">

    <div>
        <span class="page-eyebrow">CAMPUS MANAGEMENT</span>
        <h1 data-campus-title>Campuses</h1>
        <p>The buildings, floors and rooms of each campus, and the facilities in them.</p>
    </div>

    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" data-open="campus" data-title="Add Campus">+ Campus</button>

        @if($campuses->isNotEmpty())
            <button type="button" class="btn btn-secondary" data-open="building" data-title="Add Building" data-campus-preset data-values="{{ json_encode($campuses->count() === 1 ? ['campus_id' => $campuses->first()->id] : (object) []) }}">+ Building</button>
        @endif

        @if($floors->isNotEmpty())
            <button type="button" class="btn btn-primary" data-open="rooms" data-title="Add Rooms">+ Rooms</button>
        @endif
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif


<div class="content-card classroom-toolbar">

    <p class="table-secondary"><span data-campus-scope>All campuses</span> <a href="#all" data-show-all hidden>Show all campuses</a></p>

    <div class="table-search">
        <input type="search" placeholder="Search rooms, buildings, facilities..." aria-label="Search rooms and buildings" data-classroom-search>

        <select aria-label="Room status" data-classroom-status>
            <option value="">All statuses</option>
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="maintenance">Maintenance</option>
        </select>

        <button type="button" class="btn btn-secondary" data-expand-all>Expand all</button>
    </div>

</div>


@forelse($campuses as $campus)

    <section class="content-card classroom-campus" id="campus-{{ $campus->id }}" data-campus data-campus-id="{{ $campus->id }}" data-campus-name="{{ $campus->campus_name }}">

        <div class="classroom-head">

            <div>
                <h2><x-icon name="landmark" /> {{ $campus->campus_name }}</h2>
                <p>{{ collect([$campus->campus_code, $campus->address])->filter()->implode(' · ') }}</p>

                @php
                    $campusFloors = $campus->buildings->flatMap(fn ($building) => $building->floors);
                    $campusRoomCount = $campusFloors->sum(fn ($floor) => $floor->rooms->count());
                @endphp

                <div class="classroom-counts">
                    <span><strong>{{ $campus->buildings->count() }}</strong> {{ Str::plural('building', $campus->buildings->count()) }}</span>
                    <span><strong>{{ $campusFloors->count() }}</strong> {{ Str::plural('floor', $campusFloors->count()) }}</span>
                    <span><strong>{{ $campusRoomCount }}</strong> {{ Str::plural('room', $campusRoomCount) }}</span>
                </div>
            </div>

            <div class="table-actions">
                <button type="button" class="action-view" data-open="building" data-title="Add Building to {{ $campus->campus_name }}" data-values="{{ json_encode(['campus_id' => $campus->id]) }}">+ Building</button>
                <button type="button" class="action-edit" data-open="campus" data-title="Edit Campus" data-record="{{ $campus->id }}" data-values="{{ json_encode($campus->only(['campus_name', 'campus_code', 'address', 'description'])) }}">Edit</button>

                <form method="POST" action="{{ route('campuses.destroy', $campus) }}" onsubmit="return confirm('Delete this campus?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="action-delete">Delete</button>
                </form>
            </div>

        </div>

        @forelse($campus->buildings as $building)

            @php
                $buildingRoomCount = $building->floors->sum(fn ($floor) => $floor->rooms->count());
                $missingFloors = $building->missingFloorNumbers();
            @endphp

            <details
                class="classroom-building"
                id="building-{{ $building->id }}"
                data-building
                data-search="{{ mb_strtolower($building->building_name.' '.$building->building_code.' '.$campus->campus_name) }}"
            >

                <summary>
                    <span class="classroom-building-name">
                        <x-icon name="building" />
                        {{ $building->building_name }}
                        @if($building->building_code)
                            <span class="program-tag">{{ $building->building_code }}</span>
                        @endif
                    </span>

                    <span class="table-secondary">
                        {{ $building->floors->count() }} {{ Str::plural('floor', $building->floors->count()) }}
                        · {{ $buildingRoomCount }} {{ Str::plural('room', $buildingRoomCount) }}
                    </span>
                </summary>

                <div class="table-actions classroom-building-actions">

                    @if($missingFloors !== [])
                        <form method="POST" action="{{ route('buildings.floors.store', $building) }}">
                            @csrf
                            <button type="submit" class="action-view">+ Add {{ count($missingFloors) }} missing {{ Str::plural('floor', count($missingFloors)) }}</button>
                        </form>
                    @endif

                    <button type="button" class="action-view" data-open="floor" data-title="Add Floor to {{ $building->building_name }}" data-values="{{ json_encode(['building_id' => $building->id]) }}">+ Floor</button>
                    <button type="button" class="action-edit" data-open="building" data-title="Edit Building" data-record="{{ $building->id }}" data-values="{{ json_encode($building->only(['campus_id', 'building_name', 'building_code', 'number_of_floors', 'description'])) }}">Edit building</button>

                    <form method="POST" action="{{ route('buildings.destroy', $building) }}" onsubmit="return confirm('Delete this building?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-delete">Delete building</button>
                    </form>

                </div>

                @forelse($building->floors as $floor)

                    @php
                        $floorName = (int) $floor->floor_number === 0 ? 'Ground Floor' : 'Floor '.$floor->floor_number;
                    @endphp

                    <div class="classroom-floor" id="floor-{{ $floor->id }}" data-floor>

                        <div class="classroom-head">
                            <h3>{{ $floorName }}</h3>

                            <div class="table-actions">
                                <button type="button" class="action-view" data-open="rooms" data-title="Add Rooms to {{ $building->building_name }}, {{ $floorName }}" data-values="{{ json_encode(['floor_id' => $floor->id]) }}">+ Rooms</button>
                                <button type="button" class="action-edit" data-open="floor" data-title="Edit Floor" data-record="{{ $floor->id }}" data-values="{{ json_encode($floor->only(['building_id', 'floor_number', 'description'])) }}">Edit</button>

                                <form method="POST" action="{{ route('floors.destroy', $floor) }}" onsubmit="return confirm('Delete this floor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-delete">Delete</button>
                                </form>
                            </div>
                        </div>

                        @if($floor->rooms->isEmpty())
                            <p class="table-secondary">No rooms on this floor yet.</p>
                        @else

                            <ul class="classroom-rooms">

                                @foreach($floor->rooms as $room)

                                    <li
                                        data-room
                                        data-status="{{ $room->status }}"
                                        data-search="{{ mb_strtolower($room->room_name.' '.$room->facilities->pluck('facility_name')->implode(' ')) }}"
                                    >

                                        <div class="classroom-room-info">
                                            <div>
                                                <a href="{{ route('rooms.show', $room) }}" class="table-primary">{{ $room->room_name }}</a>
                                                <span class="room-status status-{{ $room->status }}">{{ ucfirst($room->status) }}</span>
                                            </div>

                                            <span class="table-secondary">
                                                {{ collect([$room->capacity ? $room->capacity.' seats' : null])->concat($room->facilities->pluck('facility_name'))->filter()->implode(' · ') ?: 'No capacity or facilities set' }}
                                            </span>
                                        </div>

                                        @php
                                            $roomValues = [
                                                ...$room->only(['floor_id', 'room_name', 'capacity', 'description']),
                                                'status' => $room->status === 'maintenance' ? 'maintenance' : 'available',
                                                'facilities' => $room->facilities->pluck('id')->all(),
                                            ];
                                        @endphp

                                        <div class="table-actions">
                                            <a href="{{ route('rooms.qr', $room) }}" class="action-view">QR</a>

                                            @if($room->isUnderMaintenance())
                                                <form method="POST" action="{{ route('rooms.maintenance.destroy', $room) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-view">Restore</button>
                                                </form>
                                            @endif

                                            <button type="button" class="action-edit" data-open="room" data-title="Edit {{ $room->room_name }}" data-record="{{ $room->id }}" data-values="{{ json_encode($roomValues) }}" @if($editRoomId === $room->id) data-open-now @endif>Edit</button>

                                            <form method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('Delete this room?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-delete">Delete</button>
                                            </form>
                                        </div>

                                    </li>

                                @endforeach

                            </ul>

                        @endif

                    </div>

                @empty

                    <p class="table-secondary">No floors yet. Add the missing floors above to start adding rooms.</p>

                @endforelse

            </details>

        @empty

            <p class="table-secondary">No buildings on this campus yet.</p>

        @endforelse

    </section>

@empty

    <div class="content-card">
        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="landmark" /></div>
            <h3>No campuses yet</h3>
            <p>Start with a campus, then add its buildings. Floors are created for you.</p>
            <button type="button" class="btn btn-primary" data-open="campus" data-title="Add Campus">+ Campus</button>
        </div>
    </div>

@endforelse

<div class="content-card" data-no-results hidden>
    <div class="small-empty-state">
        <strong>Nothing matches your search</strong>
        <span>Try another room, building, facility or campus.</span>
    </div>
</div>


<div class="content-card classroom-facilities">

    <div class="classroom-head">
        <div>
            <h2>Facilities</h2>
            <p>Equipment you can tick on any room. Click one to rename or delete it.</p>
        </div>

        <button type="button" class="btn btn-secondary" data-open="facility" data-title="Add Facility">+ Facility</button>
    </div>

    <div class="program-tags">
        @forelse($facilities as $facility)
            <button
                type="button"
                class="program-tag classroom-chip"
                data-open="facility"
                data-title="Edit Facility"
                data-record="{{ $facility->id }}"
                data-values="{{ json_encode(['facility_name' => $facility->facility_name]) }}"
                data-delete-url="{{ route('facilities.destroy', $facility) }}"
            >{{ $facility->facility_name }} · {{ $facility->rooms_count }} {{ Str::plural('room', $facility->rooms_count) }}</button>
        @empty
            <span class="table-secondary">No facilities yet.</span>
        @endforelse
    </div>

</div>


{{-- Pop-up forms --}}

<x-record-dialog kind="campus" :store-url="route('campuses.store')" :update-url="route('campuses.update', '__ID__')">
    <div class="form-group">
        <label for="campus_name">Campus Name <span class="required">*</span></label>
        <input type="text" id="campus_name" name="campus_name" maxlength="255" required>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="campus_code">Code</label>
            <input type="text" id="campus_code" name="campus_code" maxlength="50">
        </div>
        <div class="form-group">
            <label for="campus_address">Address</label>
            <input type="text" id="campus_address" name="address" maxlength="255">
        </div>
    </div>
    <div class="form-group">
        <label for="campus_description">Description</label>
        <textarea id="campus_description" name="description" rows="2"></textarea>
    </div>
</x-record-dialog>

<x-record-dialog kind="building" :store-url="route('buildings.store')" :update-url="route('buildings.update', '__ID__')">
    <div class="form-group">
        <label for="building_campus">Campus <span class="required">*</span></label>
        <select id="building_campus" name="campus_id" required>
            <option value="">Select campus</option>
            @foreach($campuses as $campus)
                <option value="{{ $campus->id }}">{{ $campus->campus_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="building_name">Building Name <span class="required">*</span></label>
        <input type="text" id="building_name" name="building_name" maxlength="255" required>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="building_code">Code</label>
            <input type="text" id="building_code" name="building_code" maxlength="50">
        </div>
        <div class="form-group">
            <label for="number_of_floors">Number of Floors <span class="required">*</span></label>
            <input type="number" id="number_of_floors" name="number_of_floors" min="1" max="100" value="1" required>
            <span class="form-help">New buildings get these floors automatically.</span>
        </div>
    </div>
    <div class="form-group">
        <label for="building_description">Description</label>
        <textarea id="building_description" name="description" rows="2"></textarea>
    </div>
</x-record-dialog>

<x-record-dialog kind="floor" :store-url="route('floors.store')" :update-url="route('floors.update', '__ID__')">
    <div class="form-group">
        <label for="floor_building">Building <span class="required">*</span></label>
        <select id="floor_building" name="building_id" required>
            <option value="">Select building</option>
            @foreach($campuses as $campus)
                @foreach($campus->buildings as $building)
                    <option value="{{ $building->id }}">{{ $building->building_name }} — {{ $campus->campus_name }}</option>
                @endforeach
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="floor_number">Floor Number <span class="required">*</span></label>
        <input type="number" id="floor_number" name="floor_number" min="0" max="255" required>
        <span class="form-help">Use 0 for the ground floor.</span>
    </div>
    <div class="form-group">
        <label for="floor_description">Description</label>
        <textarea id="floor_description" name="description" rows="2" maxlength="500"></textarea>
    </div>
</x-record-dialog>

<x-record-dialog kind="rooms" :store-url="route('rooms.store')">
    @include('admin.classrooms.partials.room-fields', ['prefix' => 'rooms', 'several' => true])
</x-record-dialog>

<x-record-dialog kind="room" :update-url="route('rooms.update', '__ID__')">
    @include('admin.classrooms.partials.room-fields', ['prefix' => 'room', 'several' => false])
</x-record-dialog>

<x-record-dialog kind="facility" :store-url="route('facilities.store')" :update-url="route('facilities.update', '__ID__')">
    <div class="form-group">
        <label for="facility_name">Facility Name <span class="required">*</span></label>
        <input type="text" id="facility_name" name="facility_name" maxlength="100" placeholder="e.g. Projector" required>
    </div>
    <button type="submit" form="delete-record" class="action-delete" data-dialog-delete hidden>Delete facility</button>
</x-record-dialog>

<form method="POST" id="delete-record" onsubmit="return confirm('Delete this facility and remove it from every room?');">
    @csrf
    @method('DELETE')
</form>

@if($errors->any())
    <script type="application/json" data-old-input>@json(session()->getOldInput())</script>
@endif

</div>

@endsection


@push('scripts')
<script src="{{ \App\Support\Asset::url('js/record-dialogs.js') }}"></script>
<script src="{{ \App\Support\Asset::url('js/classrooms.js') }}"></script>
@endpush
