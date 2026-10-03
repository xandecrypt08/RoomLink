@extends('layouts.admin')

@section('title', 'Add Subject')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Add Subject</h1>

        <p>
            Create a subject for use in classroom schedules.
        </p>

    </div>

    <a
        href="{{ route('subjects.index') }}"
        class="btn-secondary"
    >
        ← Back to Subjects
    </a>

</div>


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('subjects.store') }}"
    >

        @csrf


        <div class="form-row">

            <div class="form-group">

                <label for="subject_code">
                    Subject Code
                </label>

                <input
                    type="text"
                    id="subject_code"
                    name="subject_code"
                    value="{{ old('subject_code') }}"
                    placeholder="e.g. GE-119"
                    required
                >

                @error('subject_code')

                    <span class="form-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            <div class="form-group">

                <label for="subject_name">
                    Subject Name
                </label>

                <input
                    type="text"
                    id="subject_name"
                    name="subject_name"
                    value="{{ old('subject_name') }}"
                    placeholder="e.g. Information Management"
                    required
                >

                @error('subject_name')

                    <span class="form-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>

        </div>


        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Optional subject description..."
            >{{ old('description') }}</textarea>

            @error('description')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('subjects.index') }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Subject
            </button>

        </div>

    </form>

</div>

@endsection