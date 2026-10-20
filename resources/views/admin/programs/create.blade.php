@extends('layouts.admin')

@section('title', 'Add Program')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Add Program</h1>
        <p>Create a degree program to group subjects under.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('programs.index') }}" class="btn btn-secondary">
            ← Back to Programs
        </a>
    </div>

</div>

<div class="content-card form-card">

    <form method="POST" action="{{ route('programs.store') }}">

        @csrf

        @include('admin.programs.partials.form', ['program' => null])

        <div class="form-actions">
            <a href="{{ route('programs.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Program</button>
        </div>

    </form>

</div>

@endsection
