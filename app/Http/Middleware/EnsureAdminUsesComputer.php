<?php

namespace App\Http\Middleware;

use App\Support\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUsesComputer
{
    /**
     * Message shown when an administrator tries to use RoomLink on a phone.
     */
    public const MESSAGE = 'Administrator accounts can only be used on a computer. Please sign in from a laptop or desktop.';

    /**
     * Sign out an administrator session that is used on a phone, for example
     * after switching the phone's browser back from "desktop site".
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin() && Device::isPhone($request)) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
