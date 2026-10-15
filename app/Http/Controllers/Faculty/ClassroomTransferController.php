<?php

namespace App\Http\Controllers\Faculty;

use App\Actions\TransferRoomSession;
use App\Exceptions\RoomTransferException;
use App\Exceptions\RoomUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets faculty members move an ongoing class to another classroom while
 * keeping its session records (RLNK-24).
 */
class ClassroomTransferController extends Controller
{
    /**
     * Show the active session and the rooms it can be moved to.
     */
    public function create(Request $request): View
    {
        $activeSession = $this->activeSessionOf($this->currentFaculty($request));

        $rooms = $activeSession
            ? $this->roomsAvailableFor($activeSession)
            : new Collection;

        return view('faculty.transfer.create', compact(
            'activeSession',
            'rooms'
        ));
    }

    /**
     * Verify the selected room and transfer the session to it.
     */
    public function store(Request $request, TransferRoomSession $transferRoomSession): RedirectResponse
    {
        $faculty = $this->currentFaculty($request);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $activeSession = $this->activeSessionOf($faculty);

        if ($activeSession === null) {
            return redirect()
                ->route('faculty.transfer.create')
                ->with('error', 'You have no active session to transfer.');
        }

        $newRoom = Room::with($this->todaysSchedulesRelation())->findOrFail($validated['room_id']);

        $conflictingSchedule = $this->conflictingSchedule($newRoom, $activeSession);

        if ($conflictingSchedule !== null) {
            return back()
                ->withInput()
                ->withErrors([
                    'room_id' => "{$newRoom->room_name} is booked for {$conflictingSchedule->subject->subject_code} from "
                        .$conflictingSchedule->start_time->format('g:i A').' to '
                        .$conflictingSchedule->end_time->format('g:i A').'.',
                ]);
        }

        $previousRoomName = $activeSession->room->room_name;

        try {
            $transferRoomSession->handle($activeSession, $newRoom, $validated['reason'] ?? null);
        } catch (RoomTransferException|RoomUnavailableException $exception) {
            return back()
                ->withInput()
                ->withErrors(['room_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('faculty.transfer.create')
            ->with('success', "Class transferred from {$previousRoomName} to {$newRoom->room_name}. {$previousRoomName} is now available.");
    }

    /**
     * Rooms that are free and not booked for another class for the rest of this session.
     *
     * @return Collection<int, Room>
     */
    private function roomsAvailableFor(RoomSession $activeSession): Collection
    {
        return Room::with(['floor.building.campus', ...$this->todaysSchedulesRelation()])
            ->where('status', 'available')
            ->whereKeyNot($activeSession->room_id)
            ->whereDoesntHave('activeSession')
            ->orderBy('room_name')
            ->get()
            ->reject(fn (Room $room) => $this->conflictingSchedule($room, $activeSession) !== null)
            ->values();
    }

    /**
     * Another class scheduled in the room between now and the end of the
     * session's class (or right now, for a session with no schedule).
     * Expects the room's classSessions to be loaded with todaysSchedulesRelation().
     */
    private function conflictingSchedule(Room $room, RoomSession $activeSession): ?ClassSession
    {
        $from = now()->format('H:i:s');
        $until = $activeSession->classSession?->end_time->format('H:i:s');

        if ($until === null || $until <= $from) {
            $until = now()->addMinute()->format('H:i:s');
        }

        return $room->classSessions->first(
            fn (ClassSession $schedule) => $schedule->id !== $activeSession->class_session_id
                && $schedule->start_time->format('H:i:s') < $until
                && $schedule->end_time->format('H:i:s') > $from
        );
    }

    /**
     * Eager-load constraint for the room's active schedules today.
     *
     * @return array<string, \Closure>
     */
    private function todaysSchedulesRelation(): array
    {
        $today = $this->dayCode(now()->format('D'));

        return [
            'classSessions' => fn ($query) => $query
                ->with('subject')
                ->where('day', $today)
                ->where('status', 'active'),
        ];
    }

    private function activeSessionOf(Faculty $faculty): ?RoomSession
    {
        return $faculty->roomSessions()
            ->active()
            ->with([
                'room.floor.building.campus',
                'classSession.subject',
                'classSession.section',
            ])
            ->first();
    }

    /**
     * Get the Faculty record of the logged-in faculty member.
     */
    private function currentFaculty(Request $request): Faculty
    {
        $faculty = $request->user()->faculty;

        abort_unless(
            $faculty,
            403,
            'Your account is not linked to a faculty record.'
        );

        return $faculty;
    }

    /**
     * Convert a day name (Mon, Tue, ...) to the code stored in class_sessions.day.
     */
    private function dayCode(string $dayName): string
    {
        return [
            'Mon' => 'M',
            'Tue' => 'T',
            'Wed' => 'W',
            'Thu' => 'Th',
            'Fri' => 'F',
            'Sat' => 'S',
            'Sun' => 'Su',
        ][$dayName];
    }
}
