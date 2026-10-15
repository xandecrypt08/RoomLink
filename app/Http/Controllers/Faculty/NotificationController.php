<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Show the faculty member's notifications, newest first.
     * Unread ones are highlighted on this visit, then marked as read.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $unreadIds = $notifications->getCollection()
            ->whereNull('read_at')
            ->pluck('id')
            ->all();

        $request->user()
            ->unreadNotifications()
            ->whereIn('id', $unreadIds)
            ->update(['read_at' => now()]);

        return view('faculty.notifications.index', compact('notifications', 'unreadIds'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()
            ->route('faculty.notifications.index')
            ->with('success', 'All notifications marked as read.');
    }
}
