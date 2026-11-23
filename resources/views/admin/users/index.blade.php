@extends('layouts.admin')

@section('title', 'User Accounts')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">SYSTEM</span>

        <h1>User Accounts</h1>

        <p>
            Manage login accounts for administrators, faculty members, and students.
        </p>

    </div>

    <a
        href="{{ route('users.create', ['role' => $role]) }}"
        class="btn-primary"
    >
        + Add {{ \App\Models\User::ROLES[$role] }}
    </a>

</div>


@if(session('success'))

    <div class="alert-success">
        {{ session('success') }}
    </div>

@endif


@if(session('error'))

    <div class="alert-error">
        {{ session('error') }}
    </div>

@endif


<nav class="page-tabs" aria-label="Account types">

    @foreach(\App\Models\User::ROLE_PAGES as $value => $label)

        <a
            href="{{ route('users.index', array_filter(['role' => $value, 'search' => $search])) }}"
            class="{{ $role === $value ? 'active' : '' }}"
            @if($role === $value) aria-current="page" @endif
        >
            <x-icon :name="['admin' => 'shield', 'faculty' => 'user', 'student' => 'graduation-cap'][$value]" />
            {{ $label }}
            <span class="page-tab-count">{{ $counts[$value] ?? 0 }}</span>
        </a>

    @endforeach

</nav>


<div class="content-card">

    <div class="table-toolbar">

        <div>

            <h2>{{ \App\Models\User::ROLE_PAGES[$role] }}</h2>

            <span class="table-count">
                {{ $users->total() }}
                account{{ $users->total() != 1 ? 's' : '' }}
            </span>

        </div>


        <form
            method="GET"
            action="{{ route('users.index') }}"
            class="table-search"
        >

            <input
                type="hidden"
                name="role"
                value="{{ $role }}"
            >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search name, ID, or email..."
            >

            <button
                type="submit"
                class="btn-secondary"
            >
                Search
            </button>

        </form>

    </div>


    @if($users->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>

                        <th>Name</th>
                        <th>{{ $role === 'student' ? 'Student Number' : 'Employee Number' }}</th>
                        <th>{{ $role === 'student' ? 'Program or Department' : 'Department' }}</th>
                        @if($role === 'faculty')
                            <th>Faculty Record</th>
                        @endif
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($users as $user)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $user->full_name }}
                                </div>

                                <div class="table-secondary">
                                    {{ $user->email }}
                                </div>

                            </td>


                            <td>

                                <span class="code-badge">
                                    {{ $user->employee_student_id }}
                                </span>

                            </td>


                            <td>
                                {{ $user->department ?: '—' }}
                            </td>


                            @if($role === 'faculty')

                                <td>

                                    @if($user->faculty)
                                        {{ $user->faculty->full_name }}
                                    @else
                                        <span class="table-secondary">Not linked</span>
                                    @endif

                                </td>

                            @endif


                            <td>

                                <span class="status-badge {{ $user->is_active ? 'status-available' : 'status-maintenance' }}">
                                    {{ $user->is_active ? 'Active' : 'Deactivated' }}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('users.edit', $user) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    @unless($user->is(auth()->user()))

                                        <form
                                            method="POST"
                                            action="{{ route('users.active', $user) }}"
                                            onsubmit="return confirm('{{ $user->is_active ? 'Deactivate' : 'Reactivate' }} this account?');"
                                        >

                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="{{ $user->is_active ? 'action-delete' : 'action-view' }}"
                                            >
                                                {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                            </button>

                                        </form>

                                    @endunless

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        <div class="table-pagination">
            {{ $users->links() }}
        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                <x-icon name="shield" />
            </div>

            <h3>No accounts found</h3>

            <p>
                {{ $search ? 'Nothing here matches your search. Check the other pages above.' : 'Add the first account with the button above.' }}
            </p>

        </div>

    @endif

</div>

@endsection
