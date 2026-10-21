<?php

namespace App\Support;

use App\Models\ClassSession;

/**
 * Schedule conflict detection (RLNK-20), shared by the admin Class Sessions
 * form and teaching-load uploads.
 */
class ScheduleConflicts
{
    /**
     * Find active schedules on the same day whose time overlaps the given
     * one and that share its room, faculty member or section.
     *
     * Inactive schedules never conflict, and a schedule touching another
     * (one ends at 10:00, the next starts at 10:00) is not an overlap.
     *
     * @param  array{room_id: int|string, faculty_id: int|string, section_id: int|string, day: string, start_time: string, end_time: string, status: string}  $schedule
     * @param  int|null  $replacingFacultyId  a faculty member whose current schedule is
     *                                        about to be replaced by an uploaded teaching load,
     *                                        so their classes are left out
     * @return array<string, string> Error messages keyed by form field.
     */
    public function find(array $schedule, ?int $ignoreId = null, ?int $replacingFacultyId = null): array
    {
        if ($schedule['status'] !== 'active') {
            return [];
        }

        $overlapping = ClassSession::with(['room', 'faculty', 'subject', 'section'])
            ->where('day', $schedule['day'])
            ->where('status', 'active')
            ->where('start_time', '<', $schedule['end_time'])
            ->where('end_time', '>', $schedule['start_time'])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->when($replacingFacultyId, fn ($query) => $query->where('faculty_id', '!=', $replacingFacultyId))
            ->where(function ($query) use ($schedule) {
                $query->where('room_id', $schedule['room_id'])
                    ->orWhere('faculty_id', $schedule['faculty_id'])
                    ->orWhere('section_id', $schedule['section_id']);
            })
            ->orderBy('start_time')
            ->get();

        $messages = [
            'room_id' => 'This room is already booked',
            'faculty_id' => 'This faculty member is already teaching',
            'section_id' => 'This section already has a class',
        ];

        $errors = [];

        foreach ($messages as $field => $message) {
            $clash = $overlapping->first(fn (ClassSession $existing) => (int) $existing->{$field} === (int) $schedule[$field]);

            if ($clash !== null) {
                $errors[$field] = $message.' at that time: '.$this->describe($clash).'.';
            }
        }

        return $errors;
    }

    /**
     * Short description of a schedule for error messages,
     * e.g. "IT 101 (BSIT-1A) with Juan Dela Cruz in Room 101, 08:00–10:00".
     */
    public function describe(ClassSession $schedule): string
    {
        return sprintf(
            '%s (%s) with %s in %s, %s–%s',
            $schedule->subject->subject_code,
            $schedule->section->section_name,
            $schedule->faculty->full_name,
            $schedule->room->room_name,
            $schedule->start_time->format('H:i'),
            $schedule->end_time->format('H:i'),
        );
    }
}
