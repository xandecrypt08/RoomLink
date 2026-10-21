<?php

namespace App\Support\ScheduleImport;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Support\ScheduleConflicts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Checks the classes read from a teaching load against RoomLink, then saves
 * the ones that were kept on the review screen.
 *
 * Rooms are matched to existing rooms (faculty can't create rooms). Subjects
 * and sections are matched by code and name, and created when new. Each class
 * goes through the same conflict check as the Class Sessions form, and is also
 * checked against the other classes in the same upload.
 */
class TeachingLoadReview
{
    /**
     * Characters OCR confuses in room names, folded to one form for matching
     * (COMLABZA is COMLAB2A, COMLABIA is COMLAB1A).
     *
     * @var array<string, string>
     */
    private const ROOM_LOOKALIKES = ['O' => '0', 'Q' => '0', 'I' => '1', 'L' => '1', 'Z' => '2', 'S' => '5', 'G' => '6', 'B' => '8'];

    public function __construct(private ScheduleConflicts $conflicts) {}

    /**
     * Rooms to choose from on the review screen.
     *
     * @return Collection<int, Room>
     */
    public function rooms(): Collection
    {
        return Room::with('floor.building')->orderBy('room_name')->get();
    }

    /**
     * The room a load means by e.g. "COMLAB2A" or "Rm. 204": the same letters
     * and digits, ignoring spaces, dashes and "Room" or "Rm" in front. Failing
     * that, the only room that matches when look-alike characters are treated
     * as the same and "?" (a character OCR couldn't read) as any.
     *
     * @param  Collection<int, Room>  $rooms
     */
    public function matchRoom(string $written, Collection $rooms): ?Room
    {
        $key = $this->roomKey($written);

        if ($key === '' || $key === '?') {
            return null;
        }

        $exact = $rooms->filter(fn (Room $room) => $this->roomKey($room->room_name) === $key);

        if ($exact->count() === 1) {
            return $exact->first();
        }

        $pattern = '/^'.str_replace('\?', '.', preg_quote(strtr($key, self::ROOM_LOOKALIKES), '/')).'$/';
        $similar = $rooms->filter(fn (Room $room) => preg_match($pattern, strtr($this->roomKey($room->room_name), self::ROOM_LOOKALIKES)));

        return $similar->count() === 1 ? $similar->first() : null;
    }

    private function roomKey(string $name): string
    {
        $key = preg_replace('/[^A-Z0-9?]/', '', strtoupper($name));

        return (string) preg_replace('/^(?:ROOM|RM)(?=[0-9])/', '', $key);
    }

    /**
     * Check each class: match its room (unless one was already chosen), find
     * its subject and section, and list what stops it from being saved.
     *
     * A class identical to one already in the faculty member's schedule is a
     * duplicate: it is unticked and skipped, not a conflict. When the current
     * schedule is being replaced, the faculty member's own classes are left
     * out of the checks.
     *
     * @param  list<array{subject_code: string, title: string, section: string, day: string, start_time: string, end_time: string, room: string, room_id?: int|string|null, import?: bool}>  $rows
     * @return list<array{subject_code: string, title: string, section: string, day: string, start_time: string, end_time: string, room: string, room_id: int|null, import: bool, subject: Subject|null, new_section: bool, duplicate: bool, problems: list<string>}>
     */
    public function check(array $rows, Faculty $faculty, bool $replacing): array
    {
        $rooms = $this->rooms();
        $subjects = $this->subjectsByCode();
        $sections = $this->sectionsByName();
        $currentClasses = $replacing ? collect() : $faculty->classSessions()->where('status', 'active')->get();
        $checked = [];

        foreach (array_values($rows) as $index => $row) {
            $roomId = array_key_exists('room_id', $row)
                ? ((int) $row['room_id'] ?: null)
                : $this->matchRoom($row['room'], $rooms)?->id;

            $subject = $subjects->get(Subject::normalizeCode($row['subject_code']));
            $section = $sections->get(mb_strtolower(trim($row['section'])));
            $validTimes = $row['start_time'] !== '' && $row['end_time'] !== '' && $row['start_time'] < $row['end_time'];

            $duplicate = $subject !== null && $section !== null && $currentClasses->contains(
                fn (ClassSession $class) => $class->subject_id === $subject->id
                    && $class->section_id === $section->id
                    && $class->room_id === $roomId
                    && $class->day === $row['day']
                    && $class->start_time->format('H:i') === $row['start_time']
                    && $class->end_time->format('H:i') === $row['end_time']
            );

            $problems = [];

            if (! $duplicate) {
                if ($subject === null && trim($row['title']) === '') {
                    $problems[] = 'Enter the descriptive title of this new subject.';
                }

                if ($roomId === null) {
                    $problems[] = 'Choose the room'.(trim($row['room']) !== '' ? ' (the load says "'.$row['room'].'")' : '').'.';
                }

                if (! $validTimes) {
                    $problems[] = 'The class must end after it starts.';
                }

                if ($roomId !== null && $validTimes) {
                    $problems = [...$problems, ...array_values($this->conflicts->find([
                        'room_id' => $roomId,
                        'faculty_id' => $faculty->id,
                        'section_id' => $section->id ?? 0,
                        'day' => $row['day'],
                        'start_time' => $row['start_time'].':00',
                        'end_time' => $row['end_time'].':00',
                        'status' => 'active',
                    ], replacingFacultyId: $replacing ? $faculty->id : null))];
                }
            }

            $import = $row['import'] ?? ! $duplicate;

            // The same faculty member can't teach two classes of this upload at once.
            if ($import && ! $duplicate && $validTimes) {
                foreach ($checked as $otherIndex => $other) {
                    if ($other['import'] && ! $other['duplicate'] && $other['day'] === $row['day']
                        && $other['start_time'] < $row['end_time'] && $other['end_time'] > $row['start_time']) {
                        $problems[] = 'Overlaps row '.($otherIndex + 1).' ('.$other['subject_code'].', '.$other['start_time'].'–'.$other['end_time'].').';
                    }
                }
            }

            $checked[] = [
                'subject_code' => $subject?->subject_code ?? Subject::normalizeCode($row['subject_code']),
                'title' => $subject?->subject_name ?? trim($row['title']),
                'section' => $section?->section_name ?? trim($row['section']),
                'day' => $row['day'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'room' => $row['room'],
                'room_id' => $roomId,
                'import' => $import,
                'subject' => $subject,
                'new_section' => $section === null,
                'duplicate' => $duplicate,
                'problems' => $problems,
            ];
        }

        return $checked;
    }

    /**
     * Save the ticked classes (from check(), with no problems) as active
     * class sessions of the faculty member. When replacing, their current
     * classes are set to inactive first, which keeps their history.
     *
     * @param  list<array{subject_code: string, title: string, section: string, day: string, start_time: string, end_time: string, room_id: int|null, import: bool, subject: Subject|null, duplicate: bool}>  $rows
     * @return array{classes: int, subjects: int, sections: int, skipped: int}
     */
    public function save(array $rows, Faculty $faculty, bool $replacing): array
    {
        return DB::transaction(function () use ($rows, $faculty, $replacing): array {
            if ($replacing) {
                $faculty->classSessions()->where('status', 'active')->update(['status' => 'inactive']);
            }

            $subjects = $this->subjectsByCode();
            $sections = $this->sectionsByName();
            $counts = ['classes' => 0, 'subjects' => 0, 'sections' => 0, 'skipped' => 0];

            foreach ($rows as $row) {
                if (! $row['import']) {
                    continue;
                }

                if ($row['duplicate']) {
                    $counts['skipped']++;

                    continue;
                }

                $code = Subject::normalizeCode($row['subject_code']);
                $subject = $subjects->get($code);

                if ($subject === null) {
                    $subject = Subject::create(['subject_code' => $code, 'subject_name' => trim($row['title'])]);
                    $subjects->put($code, $subject);
                    $counts['subjects']++;
                }

                $sectionKey = mb_strtolower(trim($row['section']));
                $section = $sections->get($sectionKey);

                if ($section === null) {
                    $section = Section::create(['section_name' => trim($row['section'])]);
                    $sections->put($sectionKey, $section);
                    $counts['sections']++;
                }

                ClassSession::create([
                    'room_id' => $row['room_id'],
                    'faculty_id' => $faculty->id,
                    'subject_id' => $subject->id,
                    'section_id' => $section->id,
                    'day' => $row['day'],
                    'start_time' => $row['start_time'].':00',
                    'end_time' => $row['end_time'].':00',
                    'status' => 'active',
                ]);

                $counts['classes']++;
            }

            return $counts;
        });
    }

    /**
     * Subjects keyed by normalised code, so "IT 109" in RoomLink matches
     * "IT-109" in a load.
     *
     * @return Collection<string, Subject>
     */
    private function subjectsByCode(): Collection
    {
        return Subject::all()->keyBy(fn (Subject $subject) => Subject::normalizeCode($subject->subject_code));
    }

    /**
     * @return Collection<string, Section>
     */
    private function sectionsByName(): Collection
    {
        return Section::all()->keyBy(fn (Section $section) => mb_strtolower(trim($section->section_name)));
    }
}
