<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'The email or password is incorrect.',
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
