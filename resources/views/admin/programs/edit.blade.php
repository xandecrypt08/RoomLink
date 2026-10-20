@extends('layouts.admin')

@section('title', 'Edit Program')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Edit {{ $program->program_code }}</h1>
        <p>Update the program's code or name.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('programs.index') }}" class="btn btn-secondary">
            ← Back to Programs
        </a>
    </div>

</div>

<div class="content-card form-card">

    <form method="POST" action="{{ route('programs.update', $program) }}">

        @csrf
        @method('PUT')

        @include('admin.programs.partials.form', ['program' => $program])

        <div class="form-actions">
            <a href="{{ route('programs.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Program</button>
        </div>

    </form>

</div>

@endsection
