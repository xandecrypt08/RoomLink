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
        href="{{ asset('css/auth.css') }}"
    >

    @stack('styles')

</head>

<body>

    @yield('content')

    @stack('scripts')

</body>

</html>