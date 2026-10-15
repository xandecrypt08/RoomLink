<?php

namespace App\Http\Controllers\Faculty;

use App\Actions\EndRoomSession;
use App\Actions\StartRoomSession;
use App\Exceptions\RoomUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\RetiredQrCode;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets faculty members start a classroom session by scanning the room's QR
 * code (RLNK-21) and end it to release the room (RLNK-22).
 */
class ClassroomSessionController extends Controller
{
    /**
     * How many minutes before the scheduled start a class may be started.
     */
    public const EARLY_START_MINUTES = 15;

    /**
     * Show the room, the verification result and the Start Session button.
     * This is the page the room's QR code opens.
     */
    public function scan(Request $request, string $code): View
    {
        $room = Room::with('floor.building.campus')
            ->where('qr_code', $code)
            ->first();

        $faculty = $request->user()->faculty;

        $schedule = null;
        $blocker = null;

        if ($room === null) {
            $retiredQrCode = RetiredQrCode::with('room')->where('qr_code', $code)->first();

            $blocker = $retiredQrCode?->replacedMessage()
                ?? 'This QR code does not belong to any RoomLink classroom. Report the damaged QR code to the administrator.';
        } else {
            [$schedule, $blocker] = $this->verify($room, $faculty);
        }

        return view('faculty.sessions.scan', compact(
            'room',
            'code',
            'schedule',
            'blocker'
        ));
    }

    /**
     * Start the session after the faculty member confirms it.
     */
    public function store(Request $request, StartRoomSession $startRoomSession): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $room = Room::query()->where('qr_code', $validated['code'])->first();

        // The code was replaced or never existed; the scan page explains which.
        if ($room === null) {
            return redirect()->route('faculty.sessions.scan', $validated['code']);
        }

        $faculty = $request->user()->faculty;

        // Check again: the room or the time may have changed since the scan.
        [$schedule, $blocker] = $this->verify($room, $faculty);

        if ($blocker !== null) {
            return redirect()
                ->route('faculty.sessions.scan', $room->qr_code)
                ->with('error', $blocker);
        }

        try {
            $startRoomSession->handle($room, $faculty, $schedule);
        } catch (RoomUnavailableException $exception) {
            return redirect()
                ->route('faculty.sessions.scan', $room->qr_code)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('faculty.dashboard')
            ->with('success', "Session started in {$room->room_name} at ".now()->format('g:i A').'.');
    }

    /**
     * End the faculty member's active session and release the room.
     */
    public function end(Request $request, RoomSession $roomSession, EndRoomSession $endRoomSession): RedirectResponse
    {
        $faculty = $request->user()->faculty;

        if ($faculty === null || $roomSession->faculty_id !== $faculty->id) {
            abort(403);
        }

        $roomName = $roomSession->room->room_name;

        if (! $roomSession->isActive()) {
            return redirect()
                ->route('faculty.dashboard')
                ->with('error', "Your session in {$roomName} has already ended.");
        }

        $endRoomSession->handle($roomSession);

        return redirect()
            ->route('faculty.dashboard')
            ->with('success', 'Session ended at '.now()->format('g:i A').". {$roomName} is now available.");
    }

    /**
     * Check that the faculty member may start a session in the room right now.
     *
     * @return array{0: ?ClassSession, 1: ?string} The matching schedule, and the
     *                                             reason the session cannot start (null if it can).
     */
    private function verify(Room $room, ?Faculty $faculty): array
    {
        if ($faculty === null) {
            return [null, 'Your account is not linked to a faculty record yet. Please contact the administrator.'];
        }

        if ($room->isUnderMaintenance()) {
            return [null, "{$room->room_name} is under maintenance and cannot be used right now."];
        }

        $roomSession = $room->activeSession()->with('faculty')->first();

        if ($roomSession !== null) {
            if ($roomSession->faculty_id === $faculty->id) {
                return [null, "You already have an active session in {$room->room_name}."];
            }

            return [null, "{$room->room_name} is currently occupied by {$roomSession->faculty->full_name}."];
        }

        $ownActiveSession = $faculty->roomSessions()->active()->with('room')->first();

        if ($ownActiveSession !== null) {
            return [null, "You still have an active session in {$ownActiveSession->room->room_name}. End it before starting a new one."];
        }

        $schedule = $this->scheduledClassFor($room);

        if ($schedule === null) {
            return [null, "There is no class scheduled in {$room->room_name} right now. Classes can be started up to ".self::EARLY_START_MINUTES.' minutes before their scheduled time.'];
        }

        if ($schedule->faculty_id !== $faculty->id) {
            return [null, "{$room->room_name} is scheduled for {$schedule->faculty->full_name} ({$schedule->subject->subject_code}) right now. Submit a temporary classroom request to use this room."];
        }

        return [$schedule, null];
    }

    /**
     * The class scheduled in the room now, or starting within the early-start window.
     */
    private function scheduledClassFor(Room $room): ?ClassSession
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $earliestStart = $now->copy()->addMinutes(self::EARLY_START_MINUTES)->format('H:i:s');

        return $room->classSessions()
            ->with(['faculty', 'subject', 'section'])
            ->where('day', $this->dayCode($now->format('D')))
            ->where('status', 'active')
            ->orderBy('start_time')
            ->get()
            ->first(fn (ClassSession $schedule) => $schedule->start_time->format('H:i:s') <= $earliestStart
                && $schedule->end_time->format('H:i:s') > $currentTime);
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
