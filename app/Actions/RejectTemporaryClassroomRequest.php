<?php

namespace App\Actions;

use App\Exceptions\TemporaryRequestDecisionException;
use App\Models\TemporaryClassroomRequest;
use Illuminate\Support\Facades\DB;

class RejectTemporaryClassroomRequest
{
    /**
     * Reject a pending request (RLNK-28), optionally explaining why. The
     * requester is notified through the model's updated hook.
     *
     * @throws TemporaryRequestDecisionException when the request was already decided or has expired.
     */
    public function handle(TemporaryClassroomRequest $temporaryRequest, ?string $note = null): TemporaryClassroomRequest
    {
        return DB::transaction(function () use ($temporaryRequest, $note): TemporaryClassroomRequest {
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
                'status' => 'rejected',
                'decision_note' => $note,
                'decided_at' => now(),
            ]);

            return $temporaryRequest;
        });
    }
}
