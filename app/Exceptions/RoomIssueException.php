<?php

namespace App\Exceptions;

use App\Models\RoomIssue;
use RuntimeException;

class RoomIssueException extends RuntimeException
{
    public static function notNext(RoomIssue $issue): self
    {
        $next = $issue->nextStatus();

        return new self($next === null
            ? 'This issue is already resolved.'
            : "This issue is {$issue->status_label}, so its next status is ".RoomIssue::STATUSES[$next].'.');
    }
}
