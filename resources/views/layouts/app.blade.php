<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'RoomLink') · RoomLink</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui.css') }}">

    @stack('styles')

</head>

<body>

    <div class="dashboard-container">

        @php
            $role = auth()->user()?->role;
        @endphp

        @if($role === 'faculty')
            @include('components.faculty-sidebar')
        @elseif($role === 'student')
            @include('components.student-sidebar')
        @else
            @include('components.sidebar')
        @endif

        <div class="sidebar-backdrop" data-sidebar-close></div>

        <main class="content">

            @include('components.topnav')

            <section class="page-body">

                @yield('content')

            </section>

        </main>

    </div>

    <script>
        /* Slide-out sidebar on small screens. */
        (function () {
            var body = document.body;

            document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var open = body.classList.toggle('sidebar-open');
                    button.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            });

            document.querySelectorAll('[data-sidebar-close]').forEach(function (element) {
                element.addEventListener('click', function () {
                    body.classList.remove('sidebar-open');
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    body.classList.remove('sidebar-open');
                }
            });
        })();
    </script>

    @stack('scripts')

</body>

</html>
