<?php

namespace App\Actions;

use App\Exceptions\TemporaryRequestDecisionException;
use App\Models\TemporaryClassroomRequest;
use Illuminate\Support\Facades\DB;

class ApproveTemporaryClassroomRequest
{
    /**
     * Note left on competing requests that are turned down automatically.
     */
    public const COMPETING_REQUEST_NOTE = 'Another request for this room and time was approved.';

    /**
     * Approve a pending request (RLNK-27). The requester can then start a
     * temporary session in the room for the rest of the class time.
     *
     * Only one request per room and time slot can be approved, so any other
     * pending request for the same slot is rejected. Every decided request
     * notifies its requester through the model's updated hook.
     *
     * @throws TemporaryRequestDecisionException when the request was already decided or has expired.
     */
    public function handle(TemporaryClassroomRequest $temporaryRequest): TemporaryClassroomRequest
    {
        return DB::transaction(function () use ($temporaryRequest): TemporaryClassroomRequest {
            $temporaryRequest = TemporaryClassroomRequest::query()
                ->lockForUpdate()
                ->findOrFail($temporaryRequest->id);

            if (! $temporaryRequest->isPending()) {
                throw TemporaryRequestDecisionException::alreadyDecided($temporaryRequest);
            }

            if ($temporaryRequest->isExpiredAt(now())) {
                throw TemporaryRequestDecisionException::expired();
            }

            $temporaryRequest->update([
                'status' => 'approved',
                'decided_at' => now(),
            ]);

            TemporaryClassroomRequest::query()
                ->whereKeyNot($temporaryRequest->id)
                ->where('room_id', $temporaryRequest->room_id)
                ->where('scheduled_class_session_id', $temporaryRequest->scheduled_class_session_id)
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->startOfDay())
                ->lockForUpdate()
                ->get()
                ->each(fn (TemporaryClassroomRequest $competingRequest) => $competingRequest->update([
                    'status' => 'rejected',
                    'decision_note' => self::COMPETING_REQUEST_NOTE,
                    'decided_at' => now(),
                ]));

            return $temporaryRequest;
        });
    }
}
