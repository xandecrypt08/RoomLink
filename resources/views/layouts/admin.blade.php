<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'RoomLink')
    </title>

    <link
        rel="stylesheet"
        href="{{ asset('css/app.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/components.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}"
    >

    @stack('styles')

</head>

<body>

    <div class="dashboard-container">

        @include('components.sidebar')

        <main class="content">

            @include('components.topnav')

            <section class="page-body">

                @yield('content')

            </section>

        </main>

    </div>

    @stack('scripts')

</body>

</html>