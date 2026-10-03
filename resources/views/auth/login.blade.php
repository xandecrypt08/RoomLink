@extends('layouts.auth')

@section('title', 'Login | RoomLink')

@section('content')

<div class="login-page">

    <div class="login-background">
        <div class="login-circle circle-one"></div>
        <div class="login-circle circle-two"></div>
        <div class="login-grid"></div>
    </div>

    <div class="login-card">

        {{-- Brand --}}
        <div class="login-brand">

            <div class="brand-mark">
                <span>R</span>
                <span>L</span>
            </div>

            <div>
                <h1>RoomLink</h1>
                <p>Smart Spaces. Seamless Classes.</p>
            </div>

        </div>

        {{-- Heading --}}
        <div class="login-heading">
            <span class="login-eyebrow">ROOMLINK PORTAL</span>

            <h2>Welcome back.</h2>

            <p>
                Sign in to access your classroom management and
                faculty locator tools.
            </p>
        </div>

        {{-- Error Message --}}
        @if ($errors->any())

            <div class="login-alert">

                <div class="alert-symbol">!</div>

                <div>
                    <strong>Login failed</strong>
                    <p>{{ $errors->first() }}</p>
                </div>

            </div>

        @endif

        {{-- Login Form --}}
        <form
            method="POST"
            action="{{ route('login.submit') }}"
            class="login-form"
        >

            @csrf

            {{-- Email --}}
            <div class="login-field">

                <label for="email">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <span class="input-icon">
                        @
                    </span>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required
                        autofocus
                    >

                </div>

            </div>


            {{-- Password --}}
            <div class="login-field">

                <div class="field-label-row">

                    <label for="password">
                        Password
                    </label>

                </div>

                <div class="input-wrapper">

                    <span class="input-icon">
                        •
                    </span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                    >
                        Show
                    </button>

                </div>

            </div>


            {{-- Remember Me --}}
            <div class="login-options">

                <label class="remember-option">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                    >

                    <span>
                        Remember me
                    </span>

                </label>

            </div>


            {{-- Submit --}}
            <button
                type="submit"
                class="login-button"
            >

                <span>
                    Sign In
                </span>

                <span class="button-arrow">
                    →
                </span>

            </button>

        </form>


        {{-- Footer --}}
        <div class="login-footer">

            <div class="footer-line"></div>

            <p>
                QR Code-Based Smart Classroom Utilization
                <br>
                and Faculty Locator System
            </p>

            <span>
                Leyte Normal University
            </span>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>

    const passwordInput =
        document.getElementById('password');

    const passwordToggle =
        document.getElementById('passwordToggle');


    if (passwordInput && passwordToggle) {

        passwordToggle.addEventListener('click', function () {

            const isPassword =
                passwordInput.type === 'password';

            passwordInput.type =
                isPassword ? 'text' : 'password';

            passwordToggle.textContent =
                isPassword ? 'Hide' : 'Show';

        });

    }

</script>

@endpush