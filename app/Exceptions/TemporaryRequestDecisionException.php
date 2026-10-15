<?php

namespace App\Exceptions;

use App\Models\TemporaryClassroomRequest;
use RuntimeException;

/**
 * Thrown when a temporary classroom request can no longer be approved or rejected.
 */
class TemporaryRequestDecisionException extends RuntimeException
{
    public static function alreadyDecided(TemporaryClassroomRequest $request): self
    {
        return new self("This request was already {$request->status}.");
    }

    public static function expired(): self
    {
        return new self('This request has expired because the class time it was for is over.');
    }
}
