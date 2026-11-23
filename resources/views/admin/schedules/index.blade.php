@extends('layouts.admin')

@section('title', 'Class Sessions')

@section('content')

<div data-record-page data-session-page>

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Class Sessions</h1>
        <p>Class schedules, sections and teaching loads in one place.</p>
    </div>

    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" data-open="load" data-title="Import Teaching Load" data-values="{{ json_encode($facultyId ? ['faculty_id' => $facultyId] : (object) []) }}" @if($openForm === 'load') data-open-now @endif>
            Import Teaching Load
        </button>

        <button type="button" class="btn btn-primary" data-open="session" data-title="Add Class Session" data-values="{{ json_encode($roomId ? ['room_id' => $roomId] : (object) []) }}" @if($openForm === 'add') data-open-now @endif>
            + Add Class Session
        </button>
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if($rooms->isEmpty())
    <div class="alert alert-warning">
        There are no rooms yet, and every class needs one. <a href="{{ route('classrooms.index') }}">Add rooms in Classrooms</a> first.
    </div>
@endif


<div class="content-card">

    <details class="session-sections">

        <summary>Sections ({{ $sections->count() }})</summary>

        <div class="classroom-head">
            <p>New sections are also created when you type them in a class. Click one to rename or delete it.</p>
            <button type="button" class="btn btn-secondary" data-open="section" data-title="Add Section">+ Section</button>
        </div>

        <div class="program-tags">
            @forelse($sections as $section)
                <button
                    type="button"
                    class="program-tag classroom-chip"
                    data-open="section"
                    data-title="Edit Section"
                    data-record="{{ $section->id }}"
                    data-values="{{ json_encode($section->only(['section_name', 'description'])) }}"
                    data-delete-url="{{ route('sections.destroy', $section) }}"
                >{{ $section->section_name }} · {{ $section->class_sessions_count }} {{ Str::plural('class', $section->class_sessions_count) }}</button>
            @empty
                <span class="table-secondary">No sections yet.</span>
            @endforelse
        </div>

    </details>

</div>


<div class="content-card">

    <div class="table-toolbar">

        <div>
            <h2>Schedules</h2>
            <span class="table-count" data-session-count>{{ $schedules->count() }} {{ Str::plural('class session', $schedules->count()) }}</span>
        </div>

        <div class="table-search">
            <input type="search" value="{{ $search }}" placeholder="Search room, faculty, subject, section..." aria-label="Search class sessions" data-session-search>

            <select aria-label="Day" data-session-day>
                <option value="">All days</option>
                @foreach(\App\Models\ClassSession::DAY_NAMES as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>

            <select aria-label="Status" data-session-status>
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

    </div>

    @if($schedules->isNotEmpty())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Faculty</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($schedules as $schedule)

                        @php
                            $values = [
                                'faculty_id' => $schedule->faculty_id,
                                'room_id' => $schedule->room_id,
                                'subject_code' => $schedule->subject?->subject_code,
                                'section_name' => $schedule->section?->section_name,
                                'start_time' => $schedule->start_time?->format('H:i'),
                                'end_time' => $schedule->end_time?->format('H:i'),
                                'status' => $schedule->status,
                                'description' => $schedule->description,
                            ];
                        @endphp

                        <tr
                            data-session
                            data-day="{{ $schedule->day }}"
                            data-status="{{ $schedule->status }}"
                            data-search="{{ mb_strtolower(implode(' ', [$schedule->subject?->subject_code, $schedule->subject?->subject_name, $schedule->section?->section_name, $schedule->faculty?->full_name, $schedule->room?->room_name])) }}"
                        >

                            <td class="nowrap">
                                <div class="table-primary">{{ $schedule->day_name }}</div>
                                <div class="table-secondary">{{ $schedule->start_time?->format('g:i A') }} – {{ $schedule->end_time?->format('g:i A') }}</div>
                            </td>

                            <td>
                                <div class="table-primary">{{ $schedule->subject->subject_code ?? '—' }}</div>
                                <div class="table-secondary">{{ $schedule->subject->subject_name ?? '' }}</div>
                            </td>

                            <td>{{ $schedule->section->section_name ?? '—' }}</td>

                            <td>{{ $schedule->faculty->full_name ?? '—' }}</td>

                            <td>
                                <div class="table-primary">{{ $schedule->room->room_name ?? '—' }}</div>
                                <div class="table-secondary">{{ $schedule->room->floor->building->building_name ?? '' }}</div>
                            </td>

                            <td>
                                <span class="room-status {{ $schedule->status === 'active' ? 'status-available' : 'status-maintenance' }}">{{ ucfirst($schedule->status) }}</span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button type="button" class="action-edit" data-open="session" data-title="Edit Class Session" data-record="{{ $schedule->id }}" data-values="{{ json_encode([...$values, 'days' => [$schedule->day]]) }}" @if($editId === $schedule->id) data-open-now @endif>Edit</button>

                                    <button type="button" class="action-view" data-open="session" data-title="Copy Class Session" data-values="{{ json_encode($values) }}" title="Add the same class on another day or for another section">Copy</button>

                                    <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this class session?');">
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
            <span>Try another subject, section, faculty member or room.</span>
        </div>

    @else

        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="clock" /></div>
            <h3>No class sessions yet</h3>
            <p>Import a faculty member's teaching load, or add a class. New subjects and sections are created as you go.</p>
        </div>

    @endif

</div>


{{-- Pop-up forms --}}

<x-record-dialog kind="session" :store-url="route('schedules.store')" :update-url="route('schedules.update', '__ID__')">

    <div class="form-row">
        <div class="form-group">
            <label for="subject_code">Course No. <span class="required">*</span></label>
            <input type="text" id="subject_code" name="subject_code" list="subject-codes" maxlength="50" placeholder="e.g. IT-109" autocomplete="off" required>
        </div>
        <div class="form-group">
            <label for="section_name">Section <span class="required">*</span></label>
            <input type="text" id="section_name" name="section_name" list="section-names" maxlength="100" placeholder="e.g. AI21" autocomplete="off" required>
            <span class="form-help" data-new-section hidden>New section, created when you save.</span>
        </div>
    </div>

    <div class="form-group">
        <label for="subject_name">Descriptive Title</label>
        <input type="text" id="subject_name" name="subject_name" maxlength="150" autocomplete="off">
        <span class="form-help" data-new-subject hidden>New subject, created when you save.</span>
    </div>

    <div class="form-group">
        <label for="faculty_id">Faculty <span class="required">*</span></label>
        <select id="faculty_id" name="faculty_id" required>
            <option value="">Select faculty</option>
            @foreach($faculties as $faculty)
                <option value="{{ $faculty->id }}">{{ $faculty->last_name }}, {{ $faculty->first_name }}</option>
            @endforeach
            <option value="new">+ New faculty member</option>
        </select>

        <div class="new-record" data-new-faculty hidden>
            <div class="form-row">
                <div class="form-group">
                    <label for="faculty_first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="faculty_first_name" name="faculty_first_name" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="faculty_last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="faculty_last_name" name="faculty_last_name" maxlength="100" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="faculty_employee_id">Employee Number <span class="required">*</span></label>
                    <input type="text" id="faculty_employee_id" name="faculty_employee_id" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="faculty_email">Email</label>
                    <input type="email" id="faculty_email" name="faculty_email" maxlength="150">
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>Days <span class="required">*</span></label>
        <div class="day-options">
            @foreach(\App\Models\ClassSession::DAY_NAMES as $code => $name)
                <label class="day-option">
                    <input type="checkbox" name="days[]" value="{{ $code }}" aria-label="{{ $name }}">
                    <span>{{ Str::substr($name, 0, 3) }}</span>
                </label>
            @endforeach
        </div>
        <span class="form-help" data-days-help>Tick every day this class meets at the same time and room.</span>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="start_time">Start <span class="required">*</span></label>
            <input type="time" id="start_time" name="start_time" required>
        </div>
        <div class="form-group">
            <label for="end_time">End <span class="required">*</span></label>
            <input type="time" id="end_time" name="end_time" required>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="room_id">Room <span class="required">*</span></label>
            <select id="room_id" name="room_id" required>
                <option value="">Select room</option>
                @foreach($rooms->groupBy(fn ($room) => $room->floor->building->building_name ?? 'Other') as $buildingName => $buildingRooms)
                    <optgroup label="{{ $buildingName }}">
                        @foreach($buildingRooms as $room)
                            <option value="{{ $room->id }}">{{ $room->room_name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="session_status">Status <span class="required">*</span></label>
            <select id="session_status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="session_description">Notes</label>
        <textarea id="session_description" name="description" rows="2" maxlength="500"></textarea>
    </div>

    <x-slot:actions>
        <button type="submit" name="another" value="1" class="btn btn-secondary" data-add-only>Save and add another</button>
    </x-slot:actions>

</x-record-dialog>

<datalist id="subject-codes">
    @foreach($subjects as $subject)
        <option value="{{ $subject->subject_code }}">{{ $subject->subject_name }}</option>
    @endforeach
</datalist>

<datalist id="section-names">
    @foreach($sections as $section)
        <option value="{{ $section->section_name }}"></option>
    @endforeach
</datalist>

<x-record-dialog kind="section" :store-url="route('sections.store')" :update-url="route('sections.update', '__ID__')">
    <div class="form-group">
        <label for="edit_section_name">Section Name <span class="required">*</span></label>
        <input type="text" id="edit_section_name" name="section_name" maxlength="100" required>
    </div>
    <div class="form-group">
        <label for="section_description">Description</label>
        <textarea id="section_description" name="description" rows="2" maxlength="500"></textarea>
    </div>
    <button type="submit" form="delete-record" class="action-delete" data-dialog-delete hidden>Delete section</button>
</x-record-dialog>

<form method="POST" id="delete-record" onsubmit="return confirm('Delete this section?');">
    @csrf
    @method('DELETE')
</form>

<x-record-dialog kind="load" :store-url="route('schedules.import.preview')" submit="Find Classes" :upload="true">
    <div class="form-group">
        <label for="load_faculty">Faculty member <span class="required">*</span></label>
        <select id="load_faculty" name="faculty_id" required>
            <option value="">Select faculty</option>
            @foreach($faculties as $faculty)
                <option value="{{ $faculty->id }}">{{ $faculty->last_name }}, {{ $faculty->first_name }}</option>
            @endforeach
        </select>
        @if($faculties->isEmpty())
            <span class="form-help">No faculty yet. <a href="{{ route('faculties.create') }}">Add a faculty member</a> first.</span>
        @endif
    </div>
    <div class="form-group">
        <label for="document">Teaching load <span class="required">*</span></label>
        <input type="file" id="document" name="document" accept=".pdf,.xlsx,.csv,.docx,.txt,.jpg,.jpeg,.png,.webp,application/pdf,image/*" required>
        <span class="form-help">PDF, Excel (.xlsx or .csv), Word (.docx), or a photo. Up to 25 MB. You review every class before anything is saved.</span>
    </div>
    <div class="form-group">
        <label>These classes</label>
        <div class="facility-options">
            <label class="facility-option">
                <input type="radio" name="mode" value="add" checked>
                <span><strong>Add</strong> to the current schedule</span>
            </label>
            <label class="facility-option">
                <input type="radio" name="mode" value="replace">
                <span><strong>Replace</strong> it; current classes become inactive</span>
            </label>
        </div>
    </div>
</x-record-dialog>

@if($errors->any())
    <script type="application/json" data-old-input>@json(session()->getOldInput())</script>
@elseif(session('repeat'))
    <script type="application/json" data-repeat>@json(session('repeat'))</script>
@endif

<script type="application/json" data-subjects>@json($subjects->pluck('subject_name', 'subject_code'))</script>

</div>

@endsection


@push('scripts')
<script src="{{ \App\Support\Asset::url('js/class-sessions.js') }}"></script>
<script src="{{ \App\Support\Asset::url('js/record-dialogs.js') }}"></script>
@endpush
