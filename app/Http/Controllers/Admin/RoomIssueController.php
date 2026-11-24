<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RoomIssueException;
use App\Http\Controllers\Controller;
use App\Models\RoomIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Administrators follow reported issues until they are resolved (RLNK-33).
 */
class RoomIssueController extends Controller
{
    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->input('status'), RoomIssue::STATUSES) ? $request->input('status') : RoomIssue::STATUS_PENDING;
        $category = array_key_exists((string) $request->input('category'), RoomIssue::CATEGORIES) ? $request->input('category') : null;
        $search = $request->input('search');

        $matching = RoomIssue::query()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('assigned_to', 'like', "%{$search}%")
                    ->orWhereHas('room', fn ($room) => $room->where('room_name', 'like', "%{$search}%"));
            }));

        $counts = (clone $matching)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Work waiting longest comes first; resolved issues show the newest first.
        $issues = $matching->with(['room.floor.building', 'reporter'])
            ->where('status', $status)
            ->orderBy('id', $status === RoomIssue::STATUS_RESOLVED ? 'desc' : 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.room-issues.index', compact('issues', 'counts', 'status', 'category', 'search'));
    }

    public function show(Request $request, RoomIssue $roomIssue): View
    {
        $roomIssue->load(['room.floor.building.campus', 'room.currentMaintenance', 'reporter', 'updates' => fn ($query) => $query->orderBy('id'), 'updates.user']);

        $request->user()->unreadNotifications()
            ->where('data->room_issue_id', $roomIssue->id)
            ->update(['read_at' => now()]);

        return view('admin.room-issues.show', ['issue' => $roomIssue]);
    }

    /**
     * Move the issue to its next status.
     */
    public function update(Request $request, RoomIssue $roomIssue): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(RoomIssue::STATUSES))],
            'assigned_to' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
            'restore_room' => ['nullable', 'boolean'],
        ]);

        try {
            $roomIssue->moveTo($validated['status'], $request->user(), $validated['note'] ?? null, $validated['assigned_to'] ?? null);
        } catch (RoomIssueException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = "The issue is now {$roomIssue->status_label}.";

        if ($roomIssue->isResolved() && $request->boolean('restore_room') && $roomIssue->room->isUnderMaintenance()) {
            $roomIssue->room->endMaintenance();
            $message .= " {$roomIssue->room->room_name} can be used again.";
        }

        return redirect()->route('room-issues.show', $roomIssue)->with('success', $message);
    }

    /**
     * Generate the replacement QR code for a damaged one. The issue stays in
     * progress until the new code is posted in the room.
     */
    public function replaceQr(Request $request, RoomIssue $roomIssue): RedirectResponse
    {
        abort_unless($roomIssue->isQrIssue() && ! $roomIssue->isResolved(), 404);

        $roomIssue->room->regenerateQrCode();

        try {
            if ($roomIssue->status === RoomIssue::STATUS_PENDING) {
                $roomIssue->moveTo(RoomIssue::STATUS_ACKNOWLEDGED, $request->user());
            }

            if ($roomIssue->status === RoomIssue::STATUS_ACKNOWLEDGED) {
                $roomIssue->moveTo(RoomIssue::STATUS_IN_PROGRESS, $request->user(), 'A new QR code was generated.');
            }
        } catch (RoomIssueException) {
            // Another administrator moved it at the same moment; the new code stands.
        }

        return redirect()
            ->route('room-issues.show', $roomIssue)
            ->with('success', 'A new QR code was generated and the old one no longer works. Print it, post it in the room, then mark the issue resolved.');
    }
}
