@extends('layouts.admin')

@section('title', 'Add Section')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Add Section</h1>

        <p>
            Create a student section for classroom scheduling.
        </p>

    </div>

    <a
        href="{{ route('sections.index') }}"
        class="btn-secondary"
    >
        ← Back to Sections
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('sections.store') }}"
    >

        @csrf


        <div class="form-group">

            <label for="section_name">
                Section Name
            </label>

            <input
                type="text"
                id="section_name"
                name="section_name"
                value="{{ old('section_name') }}"
                placeholder="e.g. BSIT 3A"
                required
            >

            @error('section_name')

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
                rows="4"
                placeholder="Optional section description..."
            >{{ old('description') }}</textarea>

            @error('description')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('sections.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Section
            </button>

        </div>

    </form>

</div>

@endsection