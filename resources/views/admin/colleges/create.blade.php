@extends('layouts.admin')

@section('title', 'Add College')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Add College</h1>
        <p>Create a college to group degree programs under.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('colleges.index') }}" class="btn btn-secondary">
            ← Back to Colleges
        </a>
    </div>

</div>

<div class="content-card form-card">

    <form method="POST" action="{{ route('colleges.store') }}">

        @csrf

        @include('admin.colleges.partials.form', ['college' => null])

        <div class="form-actions">
            <a href="{{ route('colleges.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save College</button>
        </div>

    </form>

</div>

@endsection
