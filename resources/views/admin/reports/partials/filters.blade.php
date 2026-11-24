{{-- Report filters. Expects $filters, $campuses, $buildings, $rooms, $route and optionally $type. --}}
<nav class="page-tabs no-print" aria-label="Reports">
    <a href="{{ route('reports.utilization') }}" class="{{ $route === 'reports.utilization' ? 'active' : '' }}">Classroom Utilization</a>
    <a href="{{ route('reports.maintenance') }}" class="{{ $route === 'reports.maintenance' && $type === 'issues' ? 'active' : '' }}">Room Issues</a>
    <a href="{{ route('reports.maintenance', ['type' => 'maintenance']) }}" class="{{ $route === 'reports.maintenance' && $type === 'maintenance' ? 'active' : '' }}">Maintenance History</a>
</nav>

<form method="GET" action="{{ route($route) }}" class="content-card report-filters no-print" data-report-filters>

    @isset($type)
        <input type="hidden" name="type" value="{{ $type }}">
    @endisset

    <label>Campus
        <select name="campus">
            <option value="">All campuses</option>
            @foreach($campuses as $campus)
                <option value="{{ $campus->id }}" {{ $filters->campusId === $campus->id ? 'selected' : '' }}>{{ $campus->campus_name }}</option>
            @endforeach
        </select>
    </label>

    <label>Building
        <select name="building">
            <option value="">All buildings</option>
            @foreach($buildings as $building)
                <option value="{{ $building->id }}" data-campus="{{ $building->campus_id }}" {{ $filters->buildingId === $building->id ? 'selected' : '' }}>{{ $building->building_name }}</option>
            @endforeach
        </select>
    </label>

    <label>Room
        <select name="room">
            <option value="">All rooms</option>
            @foreach($rooms as $room)
                <option value="{{ $room->id }}" data-campus="{{ $room->floor?->building?->campus_id }}" data-building="{{ $room->floor?->building_id }}" {{ $filters->roomId === $room->id ? 'selected' : '' }}>{{ $room->room_name }}</option>
            @endforeach
        </select>
    </label>

    @if(($type ?? null) === 'issues')
        <label>Category
            <select name="category">
                <option value="">All categories</option>
                @foreach(\App\Models\RoomIssue::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" {{ $filters->category === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>Status
            <select name="status">
                <option value="">All statuses</option>
                @foreach(\App\Models\RoomIssue::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ $filters->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    @endif

    <label>From
        <input type="date" name="from" value="{{ $filters->from->format('Y-m-d') }}">
    </label>

    <label>To
        <input type="date" name="to" value="{{ $filters->to->format('Y-m-d') }}">
    </label>

    <div class="report-buttons">
        <button type="submit" class="btn btn-primary">Show Report</button>
        <button type="submit" name="export" value="1" class="btn btn-secondary">Export CSV</button>
        <button type="submit" name="print" value="1" class="btn btn-secondary">Print or Save PDF</button>
    </div>

</form>

@once
    @push('scripts')
    <script>
        (function () {
            var form = document.querySelector('[data-report-filters]');
            var campus = form.elements.campus;
            var building = form.elements.building;
            var room = form.elements.room;

            // Only offer buildings and rooms of the place already chosen.
            function narrow(select, matches) {
                Array.prototype.forEach.call(select.options, function (option) {
                    var fits = option.value === '' || matches(option);

                    option.hidden = !fits;
                    option.disabled = !fits;
                });

                if (select.selectedOptions[0].disabled) {
                    select.value = '';
                }
            }

            function sync() {
                narrow(building, function (option) { return !campus.value || option.dataset.campus === campus.value; });
                narrow(room, function (option) {
                    return (!campus.value || option.dataset.campus === campus.value)
                        && (!building.value || option.dataset.building === building.value);
                });
            }

            @if(request()->boolean('print'))
                // The printed report has every row, not only the first page.
                window.addEventListener('load', function () { window.print(); });
            @endif

            campus.addEventListener('change', sync);
            building.addEventListener('change', sync);
            sync();
        })();
    </script>
    @endpush
@endonce
