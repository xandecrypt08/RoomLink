@extends('layouts.admin')

@section('title', 'Edit College')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Edit {{ $college->college_code }}</h1>
        <p>Update the college's code or name.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('colleges.index') }}" class="btn btn-secondary">
            ← Back to Colleges
        </a>
    </div>

</div>

<div class="content-card form-card">

    <form method="POST" action="{{ route('colleges.update', $college) }}">

        @csrf
        @method('PUT')

        @include('admin.colleges.partials.form', ['college' => $college])

        <div class="form-actions">
            <a href="{{ route('colleges.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update College</button>
        </div>

    </form>

</div>

@endsection
