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
        data-import-form
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
                PDF (including scanned PDFs), Word (.docx), text file, or a photo of the page (JPG, PNG, WEBP). Up to 25 MB.
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
                <li>A PDF exported from Word or Excel works best and is read instantly.</li>
                <li>Scanned PDFs and photos are read with OCR, about 20 to 25 seconds a page, so please wait after clicking Find Subjects. Up to 30 pages at a time.</li>
                <li>For photos and scans, keep the page straight, in good light, with the table filling the frame.</li>
            </ul>
        </div>

        <div class="form-actions">
            <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" data-import-submit>Find Subjects</button>
        </div>

        <p class="form-help import-wait" data-import-wait hidden>
            Reading the document. Scanned pages and photos take about 20 to 25 seconds each, so keep this page open.
        </p>

    </form>

</div>

@endsection


@push('scripts')
<script>
    (function () {
        var form = document.querySelector('[data-import-form]');

        if (!form) {
            return;
        }

        // OCR can take a minute; show it is working and prevent a second upload.
        form.addEventListener('submit', function () {
            var button = form.querySelector('[data-import-submit]');

            button.disabled = true;
            button.textContent = 'Reading the document…';
            form.querySelector('[data-import-wait]').hidden = false;
        });
    })();
</script>
@endpush
