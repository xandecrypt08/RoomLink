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
        href="{{ route('users.create') }}"
        class="btn-primary"
    >
        + Add Account
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


<div class="content-card">

    <div class="table-toolbar">

        <div>

            <h2>Accounts</h2>

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

            <select name="role">

                <option value="">
                    All roles
                </option>

                @foreach(\App\Models\User::ROLES as $value => $label)

                    <option
                        value="{{ $value }}"
                        {{ $role === $value ? 'selected' : '' }}
                    >
                        {{ $label }}
                    </option>

                @endforeach

            </select>

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
                        <th>University ID</th>
                        <th>Role</th>
                        <th>Faculty Record</th>
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
                                {{ \App\Models\User::ROLES[$user->role] ?? $user->role }}
                            </td>


                            <td>

                                @if($user->faculty)
                                    {{ $user->faculty->full_name }}
                                @elseif($user->role === 'faculty')
                                    <span class="table-secondary">Not linked</span>
                                @else
                                    —
                                @endif

                            </td>


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
                ⚇
            </div>

            <h3>No accounts found</h3>

            <p>
                Try a different search or role filter.
            </p>

        </div>

    @endif

</div>

@endsection
