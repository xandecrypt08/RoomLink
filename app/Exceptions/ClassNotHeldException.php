<?php

namespace App\Exceptions;

use App\Models\ClassSession;
use RuntimeException;

/**
 * Thrown when a class cannot be marked as not held.
 */
class ClassNotHeldException extends RuntimeException
{
    public static function notScheduledToday(ClassSession $schedule): self
    {
        return new self("{$schedule->subject->subject_code} is not scheduled today.");
    }

    public static function alreadyMarked(ClassSession $schedule): self
    {
        return new self("{$schedule->subject->subject_code} is already marked as not held today.");
    }

    public static function alreadyHeld(ClassSession $schedule): self
    {
        return new self("{$schedule->subject->subject_code} was already held today and cannot be marked as not held.");
    }
}
