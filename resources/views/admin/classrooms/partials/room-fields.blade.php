{{--
    Room fields for the pop-up forms. Expects $prefix (unique field IDs),
    $several (add many rooms at once), $campuses and $facilities.
--}}
<div class="form-group">
    <label for="{{ $prefix }}_floor">Floor <span class="required">*</span></label>
    <select id="{{ $prefix }}_floor" name="floor_id" required>
        <option value="">Select floor</option>
        @foreach($campuses as $campus)
            @foreach($campus->buildings as $building)
                @if($building->floors->isNotEmpty())
                    <optgroup label="{{ $building->building_name }} — {{ $campus->campus_name }}">
                        @foreach($building->floors as $floor)
                            <option value="{{ $floor->id }}">{{ $building->building_name }}, {{ (int) $floor->floor_number === 0 ? 'Ground Floor' : 'Floor '.$floor->floor_number }}</option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="{{ $prefix }}_name">{{ $several ? 'Room Names' : 'Room Name' }} <span class="required">*</span></label>
    @if($several)
        <textarea id="{{ $prefix }}_name" name="room_name" rows="4" maxlength="5000" placeholder="COMLAB 1A&#10;COMLAB 2A&#10;CISCO LAB" required></textarea>
        <span class="form-help">Add one or many: one per line, or separated by commas. They share the details below.</span>
    @else
        <input type="text" id="{{ $prefix }}_name" name="room_name" maxlength="100" required>
    @endif
</div>

<div class="form-row">
    <div class="form-group">
        <label for="{{ $prefix }}_capacity">Capacity</label>
        <input type="number" id="{{ $prefix }}_capacity" name="capacity" min="1" placeholder="e.g. 40">
    </div>
    <div class="form-group">
        <label for="{{ $prefix }}_status">Status <span class="required">*</span></label>
        <select id="{{ $prefix }}_status" name="status" required>
            <option value="available">Available</option>
            <option value="maintenance">Maintenance</option>
        </select>
    </div>
</div>

@if($facilities->isNotEmpty())
    <div class="form-group">
        <label>Facilities</label>
        <div class="facility-options">
            @foreach($facilities as $facility)
                <label class="facility-option">
                    <input type="checkbox" name="facilities[]" value="{{ $facility->id }}">
                    <span>{{ $facility->facility_name }}</span>
                </label>
            @endforeach
        </div>
    </div>
@endif

<div class="form-group">
    <label for="{{ $prefix }}_description">Description</label>
    <textarea id="{{ $prefix }}_description" name="description" rows="2" maxlength="500"></textarea>
</div>
