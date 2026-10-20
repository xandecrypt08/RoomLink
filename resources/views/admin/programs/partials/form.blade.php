{{--
    Program fields, shared by create and edit.
    Expects $program (or null), $colleges, $groups and $selectedCollegeId.
--}}
<div class="form-row">

    <div class="form-group">

        <label for="college_id">
            College <span class="required">*</span>
        </label>

        <select id="college_id" name="college_id" required>
            <option value="">Select college</option>

            @foreach($colleges as $college)
                <option value="{{ $college->id }}" {{ (int) old('college_id', $selectedCollegeId) === $college->id ? 'selected' : '' }}>
                    {{ $college->college_name }} ({{ $college->college_code }})
                </option>
            @endforeach
        </select>

        @if($colleges->isEmpty())
            <span class="form-help">
                No colleges yet. <a href="{{ route('colleges.create') }}">Add a college</a> first.
            </span>
        @endif

        @error('college_id')
            <span class="form-error">{{ $message }}</span>
        @enderror

    </div>

    <div class="form-group">

        <label for="program_group">
            Group within the college
        </label>

        <input
            type="text"
            id="program_group"
            name="program_group"
            value="{{ old('program_group', $program?->program_group) }}"
            placeholder="e.g. Elementary Education Programs (optional)"
            maxlength="100"
            list="program-groups"
        >

        <datalist id="program-groups">
            @foreach($groups as $group)
                <option value="{{ $group }}"></option>
            @endforeach
        </datalist>

        <span class="form-help">Optional. Programs with the same group are listed together.</span>

        @error('program_group')
            <span class="form-error">{{ $message }}</span>
        @enderror

    </div>

</div>

<div class="form-row">

    <div class="form-group">

        <label for="program_code">
            Program Code <span class="required">*</span>
        </label>

        <input
            type="text"
            id="program_code"
            name="program_code"
            value="{{ old('program_code', $program?->program_code) }}"
            placeholder="e.g. BSIT or BAComm"
            maxlength="20"
            required
        >

        <span class="form-help">Written exactly as the university writes it (capitals and small letters are kept).</span>

        @error('program_code')
            <span class="form-error">{{ $message }}</span>
        @enderror

    </div>

    <div class="form-group">

        <label for="program_name">
            Program Name <span class="required">*</span>
        </label>

        <input
            type="text"
            id="program_name"
            name="program_name"
            value="{{ old('program_name', $program?->program_name) }}"
            placeholder="e.g. Bachelor of Science in Information Technology"
            maxlength="150"
            required
        >

        @error('program_name')
            <span class="form-error">{{ $message }}</span>
        @enderror

    </div>

</div>
