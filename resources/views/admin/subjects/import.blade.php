@extends('layouts.admin')

@section('title', 'Import Subjects')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Import Subjects</h1>
        <p>Upload a curriculum or prospectus and RoomLink finds each Course No. and Descriptive Title for you.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('subjects.index') }}" class="btn btn-secondary">
            ← Back to Subjects
        </a>
    </div>

</div>


@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif


<div class="content-card form-card">

    <div class="form-card-header">
        <div>
            <h2>1. Upload the document</h2>
            <p>You'll review everything that was found before anything is saved.</p>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('subjects.import.preview') }}"
        enctype="multipart/form-data"
    >

        @csrf

        <div class="form-group">

            <label for="document">
                Curriculum or prospectus <span class="required">*</span>
            </label>

            <input
                type="file"
                id="document"
                name="document"
                accept=".pdf,.docx,.txt,.jpg,.jpeg,.png,.webp,application/pdf,image/*"
                required
            >

            <span class="form-help">
                PDF, Word (.docx), text file, or a photo or scan of the page (JPG, PNG, WEBP). Up to 10 MB.
            </span>

            @error('document')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>

        @include('admin.subjects.partials.program-options', [
            'selected' => [],
            'help' => 'Every subject found will be added to the programs you select. Subjects that already exist just get these programs added.',
        ])

        <div class="import-tips">
            <strong>For the best results</strong>
            <ul>
                <li>Each subject should be on its own row, starting with the Course No. (for example <code>IT-110</code> or <code>GE 109</code>), followed by the Descriptive Title.</li>
                <li>A PDF exported from Word or Excel works best. Scanned PDFs have no text to read; upload photos of the pages instead.</li>
                <li>For photos, shoot the page straight on, in good light, with the table filling the frame.</li>
            </ul>
        </div>

        <div class="form-actions">
            <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Find Subjects</button>
        </div>

    </form>

</div>

@endsection
