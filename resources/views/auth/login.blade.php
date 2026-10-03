@extends('layouts.auth')

@section('title', 'Login | RoomLink')

@section('content')

<div class="login-page">

    {{-- Decorative background elements --}}
    <div class="login-decoration decoration-one"></div>
    <div class="login-decoration decoration-two"></div>

    <div class="login-card">

        {{-- Branding --}}
        <div class="login-brand">

            <div class="brand-mark">
                RL
            </div>

            <h1>RoomLink</h1>

            <p class="brand-tagline">
                Smart Spaces. Seamless Classes.
            </p>

        </div>


        {{-- Login heading --}}
        <div class="login-heading">

            <h2>Welcome back</h2>

            <p>
                Sign in to access the RoomLink platform.
            </p>

        </div>


        {{-- Error message --}}
        @if ($errors->any())

            <div class="login-alert">

                <span class="alert-icon">!</span>

                <div>
                    <strong>Login failed</strong>

                    <p>
                        {{ $errors->first() }}
                    </p>
                </div>

            </div>

        @endif


        {{-- Login form --}}
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
                        ●
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
                        aria-label="Show password"
                    >
                        Show
                    </button>

                </div>

            </div>


            {{-- Remember me --}}
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


            {{-- Login button --}}
            <button
                type="submit"
                class="login-button"
            >
                <span>Sign In</span>
                <span class="button-arrow">→</span>
            </button>

        </form>


        {{-- Footer --}}
        <div class="login-footer">

            <p>
                QR Code-Based Smart Classroom Utilization<br>
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

    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    if (passwordInput && passwordToggle) {

        passwordToggle.addEventListener('click', function () {

            const isPassword =
                passwordInput.type === 'password';

            passwordInput.type =
                isPassword ? 'text' : 'password';

            passwordToggle.textContent =
                isPassword ? 'Hide' : 'Show';

            passwordToggle.setAttribute(
                'aria-label',
                isPassword
                    ? 'Hide password'
                    : 'Show password'
            );

        });

    }

</script>

@endpush