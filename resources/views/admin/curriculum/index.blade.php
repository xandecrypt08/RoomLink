@extends('layouts.admin')

@section('title', 'Curriculum')

@section('content')

@php
    // Programs without a college (key 0) come last.
    $sections = $colleges->map(fn ($college) => ['college' => $college, 'programs' => $programsByCollege->get($college->id, collect())]);

    if ($programsByCollege->has(0)) {
        $sections->push(['college' => null, 'programs' => $programsByCollege->get(0)]);
    }
@endphp

<div data-record-page data-curriculum-page>

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Curriculum</h1>
        <p>Colleges, their programs and the subjects in one place.</p>
    </div>

    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" data-open="college" data-title="Add College">+ College</button>
        <button type="button" class="btn btn-secondary" data-open="program" data-title="Add Program">+ Program</button>
        <button type="button" class="btn btn-secondary" data-open="import" data-title="Import Subjects from a Document" @if($openImport) data-open-now @endif>Import Subjects</button>
        <button type="button" class="btn btn-primary" data-open="subject" data-title="Add Subject">+ Subject</button>
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif


<div class="content-card curriculum-colleges">

    <div class="classroom-head">
        <div>
            <h2><x-icon name="library" /> Colleges and Programs</h2>
            <p>Open a college to see its programs. Click a program's subject count to list its subjects.</p>
        </div>
    </div>

    @forelse($sections as $section)

        @php
            $college = $section['college'];
        @endphp

        <details class="classroom-building" id="college-{{ $college?->id ?? 'none' }}" data-college>

            <summary>
                <span class="classroom-building-name">
                    {{ $college?->college_name ?? 'Programs without a college' }}
                    @if($college)
                        <span class="program-tag">{{ $college->college_code }}</span>
                    @endif
                </span>
                <span class="table-secondary">{{ $section['programs']->count() }} {{ Str::plural('program', $section['programs']->count()) }}</span>
            </summary>

            @if($college)
                <div class="table-actions classroom-building-actions">
                    <button type="button" class="action-view" data-open="program" data-title="Add Program to {{ $college->college_code }}" data-values="{{ json_encode(['college_id' => $college->id]) }}">+ Program</button>
                    <button type="button" class="action-edit" data-open="college" data-title="Edit College" data-record="{{ $college->id }}" data-values="{{ json_encode($college->only(['college_code', 'college_name'])) }}">Edit college</button>

                    <form method="POST" action="{{ route('colleges.destroy', $college) }}" onsubmit="return confirm('Delete this college?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-delete">Delete college</button>
                    </form>
                </div>
            @endif

            <ul class="curriculum-programs">

                @forelse($section['programs']->groupBy(fn ($program) => $program->program_group ?? '') as $group => $groupPrograms)

                    @if($group !== '')
                        <li class="curriculum-group">{{ $group }}</li>
                    @endif

                    @foreach($groupPrograms as $program)
                        <li>
                            <span>
                                <strong>{{ $program->program_name }}</strong>
                                <span class="code-badge">{{ $program->program_code }}</span>
                            </span>

                            <div class="table-actions">
                                <button type="button" class="count-badge" data-show-program="{{ $program->id }}" title="Show this program's subjects">{{ $program->subjects_count }} {{ Str::plural('subject', $program->subjects_count) }}</button>
                                <button type="button" class="action-edit" data-open="program" data-title="Edit Program" data-record="{{ $program->id }}" data-values="{{ json_encode($program->only(['college_id', 'program_code', 'program_name', 'program_group'])) }}">Edit</button>

                                <form method="POST" action="{{ route('programs.destroy', $program) }}" onsubmit="return confirm('Delete this program?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-delete">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach

                @empty
                    <li class="table-secondary">No programs yet.</li>
                @endforelse

            </ul>

        </details>

    @empty
        <p class="table-secondary">No colleges yet. Add a college first, then its programs.</p>
    @endforelse

</div>


<div class="content-card" id="subjects">

    <div class="table-toolbar">

        <div>
            <h2>Subjects</h2>
            <span class="table-count" data-subject-count>{{ $subjects->count() }} {{ Str::plural('subject', $subjects->count()) }}</span>
        </div>

        <div class="table-search">
            <select aria-label="Program" data-subject-program>
                <option value="">All programs</option>
                <option value="none">No program yet</option>
                @foreach($programs->groupBy(fn ($program) => $program->college?->college_code ?? 'Other') as $collegeCode => $collegePrograms)
                    <optgroup label="{{ $collegeCode }}">
                        @foreach($collegePrograms as $program)
                            <option value="{{ $program->id }}" {{ $programId === $program->id ? 'selected' : '' }}>{{ $program->program_code }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            <input type="search" placeholder="Search subjects..." aria-label="Search subjects" data-subject-search>
        </div>

    </div>

    @if($subjects->isEmpty())

        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="book" /></div>
            <h3>No subjects yet</h3>
            <p>Import them from a curriculum document, or add them one by one.</p>
        </div>

    @else

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Course No.</th>
                        <th>Descriptive Title</th>
                        <th>Programs</th>
                        <th>Class Sessions</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($subjects as $subject)

                        <tr
                            data-subject
                            data-programs="{{ $subject->programs->pluck('id')->sort()->implode(' ') }}"
                            data-search="{{ mb_strtolower($subject->subject_code.' '.$subject->subject_name.' '.$subject->description) }}"
                        >
                            <td><span class="code-badge">{{ $subject->subject_code }}</span></td>
                            <td>
                                <div class="table-primary">{{ $subject->subject_name }}</div>
                                <div class="table-secondary">{{ $subject->description }}</div>
                            </td>
                            <td>
                                <div class="program-tags">
                                    @forelse($subject->programs as $program)
                                        <span class="program-tag">{{ $program->program_code }}</span>
                                    @empty
                                        <span class="table-secondary">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('schedules.index', ['search' => $subject->subject_code]) }}" class="count-badge" title="Show its class sessions">{{ $subject->class_sessions_count }}</a>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="action-edit" data-open="subject" data-title="Edit Subject" data-record="{{ $subject->id }}" data-values="{{ json_encode([...$subject->only(['subject_code', 'subject_name', 'description']), 'programs' => $subject->programs->pluck('id')->all()]) }}">Edit</button>

                                    <form method="POST" action="{{ route('subjects.destroy', $subject) }}" onsubmit="return confirm('Delete this subject?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="small-empty-state" data-no-results hidden>
            <strong>Nothing matches your search</strong>
            <span>Try another program, course number or title.</span>
        </div>

    @endif

</div>


{{-- Pop-up forms --}}

<x-record-dialog kind="college" :store-url="route('colleges.store')" :update-url="route('colleges.update', '__ID__')">
    <div class="form-group">
        <label for="college_code">Code <span class="required">*</span></label>
        <input type="text" id="college_code" name="college_code" maxlength="20" placeholder="e.g. CAS" required>
    </div>
    <div class="form-group">
        <label for="college_name">College Name <span class="required">*</span></label>
        <input type="text" id="college_name" name="college_name" maxlength="150" placeholder="e.g. College of Arts and Sciences" required>
    </div>
</x-record-dialog>

<x-record-dialog kind="program" :store-url="route('programs.store')" :update-url="route('programs.update', '__ID__')">
    <div class="form-group">
        <label for="program_college">College <span class="required">*</span></label>
        <select id="program_college" name="college_id" required>
            <option value="">Select college</option>
            @foreach($colleges as $college)
                <option value="{{ $college->id }}">{{ $college->college_name }} ({{ $college->college_code }})</option>
            @endforeach
        </select>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="program_code">Program Code <span class="required">*</span></label>
            <input type="text" id="program_code" name="program_code" maxlength="20" placeholder="e.g. BSIT or BAComm" required>
        </div>
        <div class="form-group">
            <label for="program_group">Group within the college</label>
            <input type="text" id="program_group" name="program_group" maxlength="100" list="program-groups" placeholder="Optional">
        </div>
    </div>
    <div class="form-group">
        <label for="program_name">Program Name <span class="required">*</span></label>
        <input type="text" id="program_name" name="program_name" maxlength="150" placeholder="e.g. Bachelor of Science in Information Technology" required>
    </div>
</x-record-dialog>

<datalist id="program-groups">
    @foreach($groups as $group)
        <option value="{{ $group }}"></option>
    @endforeach
</datalist>

<x-record-dialog kind="subject" :store-url="route('subjects.store')" :update-url="route('subjects.update', '__ID__')">
    <div class="form-group">
        <label for="subject_code">Course No. <span class="required">*</span></label>
        <input type="text" id="subject_code" name="subject_code" maxlength="50" placeholder="e.g. IT-110" required>
    </div>
    <div class="form-group">
        <label for="subject_name">Descriptive Title <span class="required">*</span></label>
        <input type="text" id="subject_name" name="subject_name" maxlength="150" required>
    </div>
    @include('admin.curriculum.partials.program-checkboxes')
    <div class="form-group">
        <label for="subject_description">Description</label>
        <textarea id="subject_description" name="description" rows="2" maxlength="500"></textarea>
    </div>
</x-record-dialog>

<x-record-dialog kind="import" :store-url="route('subjects.import.preview')" submit="Find Subjects" :upload="true">
    <div class="form-group">
        <label for="document">Curriculum or prospectus <span class="required">*</span></label>
        <input type="file" id="document" name="document" accept=".pdf,.docx,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.webp,application/pdf,image/*" required>
        <span class="form-help">PDF (including scans), Word (.docx), Excel (.xlsx or .csv), text file, or a photo. Up to 25 MB. Each subject should be on its own row, starting with the Course No. Scans and photos take about 20 to 25 seconds a page. You review everything before it is saved.</span>
    </div>
    @include('admin.curriculum.partials.program-checkboxes')
</x-record-dialog>

@if($errors->any())
    <script type="application/json" data-old-input>@json(session()->getOldInput())</script>
@endif

</div>

@endsection


@push('scripts')
<script src="{{ \App\Support\Asset::url('js/curriculum.js') }}"></script>
<script src="{{ \App\Support\Asset::url('js/record-dialogs.js') }}"></script>
@endpush
