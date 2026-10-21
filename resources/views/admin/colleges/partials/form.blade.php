{{-- College fields, shared by create and edit. Expects $college (or null). --}}
<div class="form-row">

    <div class="form-group">

        <label for="college_code">
            College Code <span class="required">*</span>
        </label>

        <input
            type="text"
            id="college_code"
            name="college_code"
            value="{{ old('college_code', $college?->college_code) }}"
            placeholder="e.g. COE"
            maxlength="20"
            required
        >

        @error('college_code')
            <span class="form-error">{{ $message }}</span>
        @enderror

    </div>

    <div class="form-group">

        <label for="college_name">
            College Name <span class="required">*</span>
        </label>

        <input
            type="text"
            id="college_name"
            name="college_name"
            value="{{ old('college_name', $college?->college_name) }}"
            placeholder="e.g. College of Education"
            maxlength="150"
            required
        >

        @error('college_name')
            <span class="form-error">{{ $message }}</span>
        @enderror

    </div>

</div>
