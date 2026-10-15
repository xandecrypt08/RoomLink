<?php

namespace App\Http\Controllers\Faculty;

use App\Actions\ApproveTemporaryClassroomRequest;
use App\Actions\RejectTemporaryClassroomRequest;
use App\Exceptions\TemporaryRequestDecisionException;
use App\Http\Controllers\Controller;
use App\Models\TemporaryClassroomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets the faculty scheduled in a room approve (RLNK-27) or reject (RLNK-28)
 * a temporary classroom request for it.
 */
class TemporaryClassroomRequestDecisionController extends Controller
{
    public function approve(
        Request $request,
        TemporaryClassroomRequest $temporaryClassroomRequest,
        ApproveTemporaryClassroomRequest $approveTemporaryClassroomRequest
    ): RedirectResponse {
        $this->authorizeDecision($request, $temporaryClassroomRequest);

        try {
            $approveTemporaryClassroomRequest->handle($temporaryClassroomRequest);
        } catch (TemporaryRequestDecisionException $exception) {
            return $this->backWithError($exception);
        }

        $temporaryClassroomRequest->load(['requester', 'room']);

        return redirect()
            ->route('faculty.temporary-requests.index')
            ->with('success', "Approved. {$temporaryClassroomRequest->requester->full_name} can now start a session in {$temporaryClassroomRequest->room->room_name}.");
    }

    public function reject(
        Request $request,
        TemporaryClassroomRequest $temporaryClassroomRequest,
        RejectTemporaryClassroomRequest $rejectTemporaryClassroomRequest
    ): RedirectResponse {
        $this->authorizeDecision($request, $temporaryClassroomRequest);

        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $rejectTemporaryClassroomRequest->handle($temporaryClassroomRequest, $validated['decision_note'] ?? null);
        } catch (TemporaryRequestDecisionException $exception) {
            return $this->backWithError($exception);
        }

        $temporaryClassroomRequest->load('requester');

        return redirect()
            ->route('faculty.temporary-requests.index')
            ->with('success', "Request from {$temporaryClassroomRequest->requester->full_name} rejected.");
    }

    /**
     * Only the faculty scheduled in the requested room may decide on the request.
     */
    private function authorizeDecision(Request $request, TemporaryClassroomRequest $temporaryClassroomRequest): void
    {
        $faculty = $request->user()->faculty;

        abort_if(
            $faculty === null || $temporaryClassroomRequest->scheduled_faculty_id !== $faculty->id,
            403
        );
    }

    private function backWithError(TemporaryRequestDecisionException $exception): RedirectResponse
    {
        return redirect()
            ->route('faculty.temporary-requests.index')
            ->with('error', $exception->getMessage());
    }
}
