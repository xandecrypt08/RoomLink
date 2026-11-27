@extends('layouts.app')

@section('title', 'Report an Issue')

@section('content')

@php
    $selectedCategory = old('category', $category);
@endphp

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM SESSIONS</span>
        <h1>Report an Issue</h1>
        <p>The administrator is notified right away, and you can follow the status in My Reports.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.issues.index') }}" class="btn btn-secondary">← My Reports</a>
    </div>

</div>


<div class="content-card form-card">

    <form method="POST" action="{{ route('faculty.issues.store') }}" enctype="multipart/form-data">

        @csrf

        <div class="form-group">
            <label for="room_id">Room <span class="required">*</span></label>
            <select id="room_id" name="room_id" required>
                <option value="">Select room</option>
                @foreach($rooms->groupBy(fn ($room) => $room->floor?->building?->building_name ?? 'Other') as $buildingName => $buildingRooms)
                    <optgroup label="{{ $buildingName }}">
                        @foreach($buildingRooms as $room)
                            <option value="{{ $room->id }}" {{ (int) old('room_id', $roomId) === $room->id ? 'selected' : '' }}>{{ $room->room_name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('room_id')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>What is the problem? <span class="required">*</span></label>
            <div class="facility-options">
                @foreach(\App\Models\RoomIssue::CATEGORIES as $value => $label)
                    <label class="facility-option">
                        <input type="radio" name="category" value="{{ $value }}" required {{ $selectedCategory === $value ? 'checked' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('category')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group" data-qr-only>
            <label class="facility-option">
                <input type="checkbox" name="qr_replacement_requested" value="1" {{ old('category') === null || old('qr_replacement_requested') ? 'checked' : '' }}>
                <span>Request a replacement QR code</span>
            </label>
        </div>

        <div class="form-group">
            <label for="description">Description <span class="required" data-description-required>*</span></label>
            <textarea id="description" name="description" rows="4" maxlength="1000" placeholder="What is wrong, and where in the room?">{{ old('description') }}</textarea>
            @error('description')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="photo">Photo</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
            <span class="form-help">Optional. Take one or choose one, up to 10 MB. Only you and the administrator can see it.</span>
            @error('photo')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('faculty.issues.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit Report</button>
        </div>

    </form>

</div>

@endsection


@push('scripts')
<script>
    (function () {
        var categories = document.querySelectorAll('[name="category"]');
        var qrOnly = document.querySelector('[data-qr-only]');
        var description = document.getElementById('description');

        function sync() {
            var chosen = document.querySelector('[name="category"]:checked');
            var isQr = Boolean(chosen) && chosen.value === 'qr_code';

            qrOnly.hidden = !isQr;
            description.required = !isQr;
            document.querySelector('[data-description-required]').hidden = isQr;
        }

        categories.forEach(function (category) { category.addEventListener('change', sync); });
        sync();
    })();
</script>
@endpush
