@extends('layouts.admin')

@section('title', 'Edit Section')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Edit Section</h1>

        <p>
            Update the section information.
        </p>

    </div>

    <a
        href="{{ route('sections.show', $section) }}"
        class="btn-secondary"
    >
        ← Back to Section
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
        action="{{ route('sections.update', $section) }}"
    >

        @csrf
        @method('PUT')


        <div class="form-group">

            <label for="section_name">
                Section Name
            </label>

            <input
                type="text"
                id="section_name"
                name="section_name"
                value="{{ old('section_name', $section->section_name) }}"
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
            >{{ old('description', $section->description) }}</textarea>

            @error('description')

                <span class="form-error">
                    {{ $message }}
                </span>

            @enderror

        </div>


        <div class="form-actions">

            <a
                href="{{ route('sections.show', $section) }}"
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