@extends('layouts.app')

@section('title', 'Upload Teaching Load')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASS SCHEDULE</span>
        <h1>Upload {{ $isAdmin ? 'a' : 'My' }} Teaching Load</h1>
        <p>
            Upload {{ $isAdmin ? "a faculty member's" : 'your' }} class list or timetable and RoomLink adds each class
            with its subject, section, day, time and room{{ $isAdmin ? '' : ', assigned to you' }}.
        </p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route($routes['back']) }}" class="btn btn-secondary">
            ← Back to {{ $isAdmin ? 'Class Sessions' : 'My Schedule' }}
        </a>
    </div>

</div>


@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif


@if(! $isAdmin && $faculty === null)

    <div class="content-card">
        <div class="small-empty-state">
            <strong>Your account isn't linked to a faculty record yet</strong>
            <span>Ask the administrator to add you under Faculty with the same employee ID as your account, then upload your load.</span>
        </div>
    </div>

@else

<div class="content-card form-card">

    <div class="form-card-header">
        <div>
            <h2>1. Upload the load</h2>
            <p>You'll review every class that was found before anything is saved.</p>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route($routes['preview']) }}"
        enctype="multipart/form-data"
        data-import-form
    >

        @csrf

        @if($isAdmin)

            <div class="form-group">

                <label for="faculty_id">
                    Faculty member <span class="required">*</span>
                </label>

                <select name="faculty_id" id="faculty_id" required>
                    <option value="">Select faculty</option>

                    @foreach($faculties as $option)
                        <option value="{{ $option->id }}" {{ old('faculty_id', request('faculty_id')) == $option->id ? 'selected' : '' }}>
                            {{ $option->last_name }}, {{ $option->first_name }}{{ $option->employee_id ? ' ('.$option->employee_id.')' : '' }}
                        </option>
                    @endforeach
                </select>

                @error('faculty_id')
                    <span class="form-error">{{ $message }}</span>
                @enderror

            </div>

        @endif

        <div class="form-group">

            <label for="document">
                Teaching load <span class="required">*</span>
            </label>

            <input
                type="file"
                id="document"
                name="document"
                accept=".pdf,.xlsx,.csv,.docx,.txt,.jpg,.jpeg,.png,.webp,application/pdf,image/*"
                required
            >

            <span class="form-help">
                PDF (including scans), Excel (.xlsx or .csv), Word (.docx), or a photo or screenshot (JPG, PNG, WEBP). Up to 25 MB.
            </span>

            @error('document')
                <span class="form-error">{{ $message }}</span>
            @enderror

        </div>

        <div class="form-group">

            <label>These classes</label>

            <div class="facility-options">

                <label class="facility-option">
                    <input type="radio" name="mode" value="add" {{ old('mode', 'add') === 'add' ? 'checked' : '' }}>
                    <span>
                        <strong>Add to the current schedule</strong>
                        · classes already there are skipped
                    </span>
                </label>

                <label class="facility-option">
                    <input type="radio" name="mode" value="replace" {{ old('mode') === 'replace' ? 'checked' : '' }}>
                    <span>
                        <strong>Replace the current schedule</strong>
                        · for a new semester; current classes become inactive
                    </span>
                </label>

            </div>

        </div>

        <div class="import-tips">
            <strong>For the best results</strong>
            <ul>
                <li>Each class should be on its own row with the subject code, the section and the schedule, for example <code>AI21 · IT-109 · 10:30 am-12:30 pm S COMLAB2A</code>.</li>
                <li>Classes on several days (<code>MTh</code>, <code>TTh</code>, <code>MWF</code>) are added once for each day.</li>
                <li>The room is matched to the rooms in RoomLink; if one isn't recognised you can choose it on the next page.</li>
                <li>Exports from the registrar's system (PDF or Excel) are read instantly. Photos and screenshots are read with OCR and take a little longer.</li>
            </ul>
        </div>

        <div class="form-actions">
            <a href="{{ route($routes['back']) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" data-import-submit>Find Classes</button>
        </div>

        <p class="form-help import-wait" data-import-wait hidden>
            Reading the load. Photos and scanned pages take about 20 seconds each, so keep this page open.
        </p>

    </form>

</div>

@endif

@endsection


@push('scripts')
<script>
    (function () {
        var form = document.querySelector('[data-import-form]');

        if (!form) {
            return;
        }

        // OCR can take a while; show it is working and prevent a second upload.
        form.addEventListener('submit', function () {
            var button = form.querySelector('[data-import-submit]');

            button.disabled = true;
            button.textContent = 'Reading the load…';
            form.querySelector('[data-import-wait]').hidden = false;
        });
    })();
</script>
@endpush
