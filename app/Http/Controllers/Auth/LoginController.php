<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminUsesComputer;
use App\Support\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Failed sign-ins allowed per email and network address before a lockout.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * How long the lockout lasts, in seconds.
     */
    public const LOCKOUT_SECONDS = 60;

    /**
     * The login screen adapts to the device: phones are for faculty and
     * students, computers for administrators and faculty.
     */
    public function showLogin(Request $request)
    {
        return view('auth.login', [
            'isPhone' => Device::isPhone($request),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => "Too many sign-in attempts. Please try again in {$seconds} seconds.",
                ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'The email or password is incorrect.',
                ]);
        }

        RateLimiter::clear($throttleKey);

        if (Auth::user()->isAdmin() && Device::isPhone($request)) {
            Auth::logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => EnsureAdminUsesComputer::MESSAGE,
                ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the administrator.',
                ]);
        }

        $dashboardRoute = Auth::user()->dashboardRoute();

        if ($dashboardRoute === null) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your account does not have a valid role.',
                ]);
        }

        $request->session()->regenerate();

        return redirect($this->intendedUrlFor(Auth::user()->role) ?? route($dashboardRoute));
    }

    /**
     * Attempts are counted per email and network address, so one person
     * guessing can't lock everyone else out of the same account.
     */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();
    }

    /**
     * The page the user tried to open before logging in (such as a scanned
     * room QR code), if it belongs to their own role's area.
     */
    private function intendedUrlFor(string $role): ?string
    {
        $intendedUrl = session()->pull('url.intended');

        if (! is_string($intendedUrl) || ! str_starts_with($intendedUrl, url($role).'/')) {
            return null;
        }

        return $intendedUrl;
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
