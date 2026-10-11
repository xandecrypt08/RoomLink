<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\TemporaryClassroomRequest;
use App\Notifications\TemporaryClassroomRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemporaryClassroomRequestController extends Controller
{
    public function index(Request $request): View
    {
        $faculty = $this->currentFaculty($request);

        $myRequests = $faculty->temporaryClassroomRequests()
            ->with([
                'room.floor.building.campus',
                'scheduledFaculty',
                'requesterClassSession.subject',
            ])
            ->latest()
            ->get();

        $incomingRequests = $faculty->incomingTemporaryClassroomRequests()
            ->with([
                'room.floor.building.campus',
                'requester',
                'requesterClassSession.subject',
                'requesterClassSession.section',
            ])
            ->latest()
            ->get();

        /*
         * Highlight requests the faculty member has not seen yet,
         * then mark their notifications as read.
         */
        $unreadNotifications = $request->user()
            ->unreadNotifications()
            ->where('type', TemporaryClassroomRequested::class)
            ->get();

        $newRequestIds = $unreadNotifications
            ->pluck('data.temporary_classroom_request_id')
            ->all();

        $unreadNotifications->markAsRead();

        return view('faculty.temporary-requests.index', compact(
            'faculty',
            'myRequests',
            'incomingRequests',
            'newRequestIds'
        ));
    }

    public function create(Request $request): View
    {
        $faculty = $this->currentFaculty($request);

        $code = trim((string) $request->query('code', ''));

        $room = null;
        $scheduledSession = null;

        if ($code !== '') {
            $room = Room::with('floor.building.campus')
                ->where('qr_code', $code)
                ->first();

            $scheduledSession = $room?->scheduledClassSessionAt(now());
        }

        $mySessionsToday = $faculty->classSessions()
            ->with(['room', 'subject', 'section'])
            ->where('day', ClassSession::dayCodeFor(now()))
            ->where('status', 'active')
            ->orderBy('start_time')
            ->get();

        return view('faculty.temporary-requests.create', compact(
            'faculty',
            'code',
            'room',
            'scheduledSession',
            'mySessionsToday'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $faculty = $this->currentFaculty($request);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'requester_class_session_id' => [
                'nullable',
                Rule::exists('class_sessions', 'id')
                    ->where('faculty_id', $faculty->id),
            ],
            'reason' => 'required|string|max:500',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        if ($room->status === 'maintenance') {
            return back()
                ->withInput()
                ->withErrors([
                    'room_id' => 'This room is under maintenance and cannot be requested.',
                ]);
        }

        $scheduledSession = $room->scheduledClassSessionAt(now());

        if ($scheduledSession && $scheduledSession->faculty_id === $faculty->id) {
            return back()
                ->withInput()
                ->withErrors([
                    'room_id' => 'You are already scheduled in this room right now.',
                ]);
        }

        $hasPendingRequest = $faculty->temporaryClassroomRequests()
            ->where('room_id', $room->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingRequest) {
            return back()
                ->withInput()
                ->withErrors([
                    'room_id' => 'You already have a pending request for this room.',
                ]);
        }

        $temporaryRequest = TemporaryClassroomRequest::create([
            'requester_id' => $faculty->id,
            'requester_class_session_id' => $validated['requester_class_session_id'] ?? null,
            'room_id' => $room->id,
            'scheduled_class_session_id' => $scheduledSession?->id,
            'scheduled_faculty_id' => $scheduledSession?->faculty_id,
            'reason' => $validated['reason'],
        ]);

        $scheduledSession?->faculty?->user?->notify(
            new TemporaryClassroomRequested($temporaryRequest)
        );

        return redirect()
            ->route('faculty.temporary-requests.index')
            ->with('success', $scheduledSession
                ? 'Request submitted. The scheduled faculty has been notified.'
                : 'Request submitted. No class is scheduled in this room right now.');
    }

    /**
     * Get the Faculty record of the logged-in faculty member.
     */
    private function currentFaculty(Request $request): Faculty
    {
        $user = $request->user();

        abort_unless(
            $user->isFaculty() && $user->faculty,
            403,
            'Only faculty members with a faculty record can request classrooms.'
        );

        return $user->faculty;
    }
}
