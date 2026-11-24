<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Take a room out of use for maintenance and restore it (RLNK-34). Sessions
 * can't be started in it meanwhile; one already running may finish.
 */
class RoomMaintenanceController extends Controller
{
    public function store(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'room_issue_id' => ['nullable', Rule::exists('room_issues', 'id')->where('room_id', $room->id)],
        ]);

        if ($room->isUnderMaintenance()) {
            return back()->with('error', "{$room->room_name} is already under maintenance.");
        }

        $issue = isset($validated['room_issue_id']) ? RoomIssue::find($validated['room_issue_id']) : null;

        $room->startMaintenance($validated['reason'] ?? null, $issue);

        return back()->with('success', "{$room->room_name} is now under maintenance. Sessions can't be started in it.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        if (! $room->isUnderMaintenance()) {
            return back()->with('error', "{$room->room_name} is not under maintenance.");
        }

        $room->endMaintenance();

        return back()->with('success', "{$room->room_name} was restored and can be used again.");
    }
}
