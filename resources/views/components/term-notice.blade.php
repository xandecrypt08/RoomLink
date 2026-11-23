{{-- Says so when the semester being viewed isn't the one running now. --}}
@php
    $selected = \App\Support\SelectedTerm::get();
@endphp

@unless($selected->is_current)
    <div class="alert alert-warning">
        You're viewing <strong>{{ $selected->label }}</strong>. Today's classes and QR scanning still follow {{ \App\Models\AcademicTerm::current()->label }}.
    </div>
@endunless
