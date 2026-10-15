<?php

namespace App\Http\Controllers;

use App\Models\RoomSession;
use Illuminate\View\View;

class FacultyLocatorController extends Controller
{
    /**
     * Show where faculty members are teaching right now. Only sessions that
     * are still running are listed, so a location disappears once it ends.
     */
    public function index(): View
    {
        $now = now();

        $locatedSessions = RoomSession::query()
            ->active()
            ->where('started_at', '>=', $now->copy()->startOfDay())
            ->with([
                'faculty',
                'room.floor.building.campus',
                'classSession.subject',
                'classSession.section',
            ])
            ->get()
            ->filter(fn (RoomSession $session) => $session->isLocatableAt($now))
            ->sortBy(fn (RoomSession $session) => $session->faculty->last_name.' '.$session->faculty->first_name)
            ->values();

        return view('locator.index', compact('locatedSessions'));
    }
}
