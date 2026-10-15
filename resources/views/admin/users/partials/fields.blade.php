{{-- Account fields shared by the create and edit forms. Expects $faculties and an optional $user. --}}

<div class="form-row">

    <div class="form-group">

        <label for="employee_student_id">
            University ID
        </label>

        <input
            type="text"
            id="employee_student_id"
            name="employee_student_id"
            value="{{ old('employee_student_id', $user->employee_student_id ?? '') }}"
            placeholder="Employee or student ID, e.g. FAC001 or 2024-0001"
            required
        >

        @error('employee_student_id')
            <span class="form-error">
                {{ $message }}
            </span>
        @enderror

    </div>


    <div class="form-group">

        <label for="role">
            Role
        </label>

        <select
            id="role"
            name="role"
            required
        >

            <option value="">
                Select a role
            </option>

            @foreach(\App\Models\User::ROLES as $value => $label)

                <option
                    value="{{ $value }}"
                    {{ old('role', $user->role ?? '') === $value ? 'selected' : '' }}
                >
                    {{ $label }}
                </option>

            @endforeach

        </select>

        @error('role')
            <span class="form-error">
                {{ $message }}
            </span>
        @enderror

    </div>

</div>


<div class="form-row">

    <div class="form-group">

        <label for="first_name">
            First Name
        </label>

        <input
            type="text"
            id="first_name"
            name="first_name"
            value="{{ old('first_name', $user->first_name ?? '') }}"
            required
        >

        @error('first_name')
            <span class="form-error">
                {{ $message }}
            </span>
        @enderror

    </div>


    <div class="form-group">

        <label for="middle_name">
            Middle Name
        </label>

        <input
            type="text"
            id="middle_name"
            name="middle_name"
            value="{{ old('middle_name', $user->middle_name ?? '') }}"
        >

        @error('middle_name')
            <span class="form-error">
                {{ $message }}
            </span>
        @enderror

    </div>

</div>


<div class="form-row">

    <div class="form-group">

        <label for="last_name">
            Last Name
        </label>

        <input
            type="text"
            id="last_name"
            name="last_name"
            value="{{ old('last_name', $user->last_name ?? '') }}"
            required
        >

        @error('last_name')
            <span class="form-error">
                {{ $message }}
            </span>
        @enderror

    </div>


    <div class="form-group">

        <label for="department">
            Department
        </label>

        <input
            type="text"
            id="department"
            name="department"
            value="{{ old('department', $user->department ?? '') }}"
            placeholder="e.g. College of Computing"
        >

        @error('department')
            <span class="form-error">
                {{ $message }}
            </span>
        @enderror

    </div>

</div>


<div class="form-group">

    <label for="email">
        Email
    </label>

    <input
        type="email"
        id="email"
        name="email"
        value="{{ old('email', $user->email ?? '') }}"
        placeholder="name@lnu.edu.ph"
        required
    >

    @error('email')
        <span class="form-error">
            {{ $message }}
        </span>
    @enderror

</div>


<div class="form-group">

    <label for="faculty_id">
        Linked Faculty Record
    </label>

    <select
        id="faculty_id"
        name="faculty_id"
    >

        <option value="">
            Not linked
        </option>

        @foreach($faculties as $faculty)

            <option
                value="{{ $faculty->id }}"
                {{ (string) old('faculty_id', $user->faculty->id ?? '') === (string) $faculty->id ? 'selected' : '' }}
            >
                {{ $faculty->full_name }} ({{ $faculty->employee_id }})
            </option>

        @endforeach

    </select>

    <span class="form-help">
        Faculty accounts only. Linking gives the account the faculty member's schedules.
        Records already linked to another account are not listed.
    </span>

    @error('faculty_id')
        <span class="form-error">
            {{ $message }}
        </span>
    @enderror

</div>
