@extends('layouts.admin')

@section('title', 'Edit Subject')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Edit Subject</h1>

        <p>
            Update the subject information.
        </p>

    </div>

    <a
        href="{{ route('subjects.show', $subject) }}"
        class="btn-secondary"
    >
        ← Back to Subject
    </a>

</div>


@if($errors->any())

    <div class="alert-error">

        <strong>Please fix the following:</strong>

        <ul>

            @foreach($errors->all() as $error)

                <li>{{ $error }}</li>

            @endforeach

        </ul>

    </div>

@endif


<div class="content-card form-card">

    <form
        method="POST"
        action="{{ route('subjects.update', $subject) }}"
    >

        @csrf
        @method('PUT')


        <div class="form-row">

            <div class="form-group">

                <label for="subject_code">
                    Subject Code
                </label>

                <input
                    type="text"
                    id="subject_code"
                    name="subject_code"
                    value="{{ old('subject_code', $subject->subject_code) }}"
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
                    value="{{ old('subject_name', $subject->subject_name) }}"
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
            >{{ old('description', $subject->description) }}</textarea>

            @error('description')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('subjects.show', $subject) }}"
                class="btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary"
            >
                Save Changes
            </button>

        </div>

    </form>

</div>

@endsection