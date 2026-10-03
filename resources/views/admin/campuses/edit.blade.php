@extends('layouts.admin')

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Campus</h1>
        <p>Update campus information.</p>
    </div>
</div>

<div class="form-card">

    <form method="POST" action="{{ route('campuses.update', $campus) }}">

        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="campus_name">Campus Name</label>

            <input
                type="text"
                id="campus_name"
                name="campus_name"
                value="{{ old('campus_name', $campus->campus_name) }}"
                required
            >

            @error('campus_name')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="campus_code">Campus Code</label>

            <input
                type="text"
                id="campus_code"
                name="campus_code"
                value="{{ old('campus_code', $campus->campus_code) }}"
                required
            >

            @error('campus_code')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="address">Address</label>

            <input
                type="text"
                id="address"
                name="address"
                value="{{ old('address', $campus->address) }}"
                required
            >

            @error('address')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                rows="4"
            >{{ old('description', $campus->description) }}</textarea>

            @error('description')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-actions">

            <a
                href="{{ route('campuses.index') }}"
                class="btn-light"
            >
                Cancel
            </a>

            <button type="submit" class="btn-primary">
                Update Campus
            </button>

        </div>

    </form>

</div>

@endsection