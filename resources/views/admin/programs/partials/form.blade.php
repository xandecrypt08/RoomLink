{{-- Program fields, shared by create and edit. Expects $program (or null). --}}
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
            placeholder="e.g. BSIT"
            maxlength="20"
            required
        >

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
