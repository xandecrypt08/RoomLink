<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Tells phones apart from computers so the login screen can adapt and
 * administrators can be kept to computers.
 *
 * This is a usage policy, not a security boundary: browsers report their own
 * device type and a phone can ask for the "desktop site". Tablets count as
 * computers.
 */
class Device
{
    /**
     * Phone browsers: iPhone/iPod, Android phones (Android tablets leave out
     * "Mobile"), and older mobile browsers.
     */
    private const PHONE_USER_AGENT = '/iPhone|iPod|Android.*Mobile|Windows Phone|IEMobile|BlackBerry|Opera Mini/i';

    public static function isPhone(Request $request): bool
    {
        // Chromium browsers send this hint on secure pages: ?1 = mobile, ?0 = not.
        $mobileHint = $request->header('Sec-CH-UA-Mobile');

        if ($mobileHint === '?1') {
            return true;
        }

        if ($mobileHint === '?0') {
            return false;
        }

        return (bool) preg_match(self::PHONE_USER_AGENT, (string) $request->userAgent());
    }
}
