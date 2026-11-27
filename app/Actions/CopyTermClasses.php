<?php

namespace App\Actions;

use App\Models\AcademicTerm;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Support\ScheduleConflicts;
use Illuminate\Support\Facades\DB;

/**
 * Copies the active classes of one term into another. A class that clashes
 * with one already in the target term, including a copy made before, is
 * skipped, so copying twice changes nothing.
 */
class CopyTermClasses
{
    public function __construct(private ScheduleConflicts $conflicts) {}

    /**
     * @return array{copied: int, skipped: list<string>}
     */
    public function handle(AcademicTerm $from, AcademicTerm $to, ?Faculty $faculty = null): array
    {
        return DB::transaction(function () use ($from, $to, $faculty): array {
            $copied = 0;
            $skipped = [];

            $classes = ClassSession::with(['room', 'faculty', 'subject', 'section'])
                ->inTerm($from)
                ->where('status', 'active')
                ->when($faculty, fn ($query) => $query->where('faculty_id', $faculty->id))
                ->orderedByWeek()
                ->get();

            foreach ($classes as $class) {
                $copy = [
                    'academic_term_id' => $to->id,
                    'room_id' => $class->room_id,
                    'faculty_id' => $class->faculty_id,
                    'subject_id' => $class->subject_id,
                    'section_id' => $class->section_id,
                    'day' => $class->day,
                    'start_time' => $class->start_time->format('H:i:s'),
                    'end_time' => $class->end_time->format('H:i:s'),
                    'status' => 'active',
                    'description' => $class->description,
                ];

                if ($this->conflicts->find($copy) !== []) {
                    $skipped[] = "{$class->day_name}: ".$this->conflicts->describe($class);

                    continue;
                }

                ClassSession::create($copy);
                $copied++;
            }

            return ['copied' => $copied, 'skipped' => $skipped];
        });
    }
}
