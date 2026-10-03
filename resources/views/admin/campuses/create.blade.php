@extends('layouts.admin')

@section('title', 'Add Campus')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Add Campus</h1>

        <p>Create a new campus for RoomLink.</p>
    </div>

    <a href="{{ route('campuses.index') }}" class="btn-secondary">
        ← Back to Campuses
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('campuses.store') }}"
    >

        @csrf


        <div class="form-group">

            <label for="campus_name">
                Campus Name
            </label>

            <input
                type="text"
                id="campus_name"
                name="campus_name"
                value="{{ old('campus_name') }}"
                placeholder="e.g. Main Campus"
                required
            >

            @error('campus_name')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        <div class="form-group">

            <label for="campus_code">
                Campus Code
            </label>

            <input
                type="text"
                id="campus_code"
                name="campus_code"
                value="{{ old('campus_code') }}"
                placeholder="e.g. MC"
            >

            @error('campus_code')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        <div class="form-group">

            <label for="address">
                Address
            </label>

            <input
                type="text"
                id="address"
                name="address"
                value="{{ old('address') }}"
                placeholder="Enter campus address"
            >

            @error('address')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                placeholder="Optional campus description"
            >{{ old('description') }}</textarea>

            @error('description')
                <span class="form-error">
                    {{ $message }}
                </span>
            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('campuses.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Campus
            </button>

        </div>

    </form>

</div>

@endsection