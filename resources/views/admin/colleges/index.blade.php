@extends('layouts.admin')

@section('title', 'College Management')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>College Management</h1>
        <p>Colleges of the university. Each degree program belongs to one.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('programs.index') }}" class="btn btn-secondary">
            View Programs
        </a>

        <a href="{{ route('colleges.create') }}" class="btn btn-primary">
            + Add College
        </a>
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif


<div class="content-card">

    <div class="table-toolbar">
        <div>
            <h2>Colleges</h2>
            <span class="table-count">
                {{ $colleges->count() }} college{{ $colleges->count() != 1 ? 's' : '' }}
            </span>
        </div>
    </div>

    @if($colleges->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>College</th>
                        <th>Code</th>
                        <th>Programs</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($colleges as $college)

                        <tr>

                            <td>
                                <div class="table-primary">{{ $college->college_name }}</div>
                            </td>

                            <td>
                                <span class="code-badge">{{ $college->college_code }}</span>
                            </td>

                            <td>
                                <span class="count-badge">{{ $college->programs_count }}</span>
                            </td>

                            <td>
                                <div class="table-actions">

                                    <a href="{{ route('colleges.edit', $college) }}" class="action-edit">
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('colleges.destroy', $college) }}"
                                        onsubmit="return confirm('Delete this college?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="action-delete">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                <x-icon name="library" />
            </div>

            <h3>No colleges yet</h3>

            <p>Add colleges such as the College of Education (COE).</p>

            <a href="{{ route('colleges.create') }}" class="btn btn-primary">
                + Add College
            </a>

        </div>

    @endif

</div>

@endsection
