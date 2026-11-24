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
        href="{{ \App\Support\Asset::url('css/app.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ \App\Support\Asset::url('css/auth.css') }}"
    >

    <link rel="icon" href="{{ \App\Support\Asset::url('images/logo.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ \App\Support\Asset::url('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ \App\Support\Asset::url('images/apple-touch-icon.png') }}">

</head>

<body>

    @yield('content')

    @stack('scripts')

</body>

</html>