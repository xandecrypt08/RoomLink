<?php

namespace App\Http\Controllers\Faculty;

use App\Actions\MarkClassNotHeld;
use App\Exceptions\ClassNotHeldException;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets a faculty member mark one of today's scheduled classes as not
 * conducted, record why, and release the room (RLNK-25).
 */
class ClassNotHeldController extends Controller
{
    public function create(Request $request, ClassSession $classSession): View
    {
        $this->authorizeOwnSchedule($request, $classSession);

        $classSession->load(['room.floor.building', 'subject', 'section']);

        return view('faculty.not-held.create', [
            'schedule' => $classSession,
        ]);
    }

    public function store(Request $request, ClassSession $classSession, MarkClassNotHeld $markClassNotHeld): RedirectResponse
    {
        $this->authorizeOwnSchedule($request, $classSession);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $classSession->load(['room', 'subject']);

        try {
            $markClassNotHeld->handle($classSession, $validated['reason']);
        } catch (ClassNotHeldException $exception) {
            return redirect()
                ->route('faculty.dashboard')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('faculty.dashboard')
            ->with('success', "{$classSession->subject->subject_code} is marked as not held today. {$classSession->room->room_name} is free for other classes.");
    }

    /**
     * Only the faculty member who teaches the class may mark it.
     */
    private function authorizeOwnSchedule(Request $request, ClassSession $classSession): void
    {
        $faculty = $request->user()->faculty;

        abort_if($faculty === null || $classSession->faculty_id !== $faculty->id, 403);
    }
}
