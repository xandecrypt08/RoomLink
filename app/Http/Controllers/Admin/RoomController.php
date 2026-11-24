<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Support\SelectedTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    /**
     * Add one or more rooms to a floor. Several names, one per line or
     * separated by commas, share the same capacity, status and facilities.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'room_name' => 'required|string|max:5000',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|in:available,maintenance',
            'description' => 'nullable|string|max:500',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $names = $this->roomNames($validated['room_name'], (int) $validated['floor_id']);

        DB::transaction(function () use ($names, $validated): void {
            foreach ($names as $name) {
                $room = Room::create([
                    'floor_id' => $validated['floor_id'],
                    'room_name' => $name,
                    'capacity' => $validated['capacity'] ?? null,
                    'status' => $validated['status'],
                    'description' => $validated['description'] ?? null,
                ]);

                $room->facilities()->sync($validated['facilities'] ?? []);
            }
        });

        return redirect()
            ->route('classrooms.index')
            ->withFragment("floor-{$validated['floor_id']}")
            ->with('success', count($names) === 1 ? "{$names[0]} added." : count($names).' rooms added.');
    }

    /**
     * The room names typed into the add form.
     *
     * @return list<string>
     */
    private function roomNames(string $input, int $floorId): array
    {
        $names = collect(preg_split('/[\r\n,]+/', $input))
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique(fn (string $name) => mb_strtolower($name))
            ->values();

        $existing = Room::where('floor_id', $floorId)->pluck('room_name')
            ->map(fn (string $name) => mb_strtolower($name));

        $duplicates = $names->filter(fn (string $name) => $existing->contains(mb_strtolower($name)));

        $error = match (true) {
            $names->isEmpty() => 'Enter at least one room name.',
            $names->contains(fn (string $name) => mb_strlen($name) > 100) => 'Each room name must be 100 characters or fewer.',
            $duplicates->isNotEmpty() => 'Already on this floor: '.$duplicates->implode(', ').'.',
            default => null,
        };

        if ($error !== null) {
            throw ValidationException::withMessages(['room_name' => $error]);
        }

        return $names->all();
    }

    public function show(Room $room)
    {
        $room->load([
            'floor.building.campus',
            'facilities',
            'currentMaintenance.starter',
            'issues' => fn ($query) => $query->open()->latest('id'),
            'classSessions' => fn ($query) => $query->inTerm(SelectedTerm::get())->orderedByWeek(),
            'classSessions.faculty',
            'classSessions.subject',
            'classSessions.section',
        ]);

        return view('admin.rooms.show', compact('room'));
    }

    /**
     * Rooms are edited in a pop-up on the Campuses page.
     */
    public function edit(Room $room): RedirectResponse
    {
        return redirect()->route('classrooms.index', ['edit' => $room->id]);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'room_name' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|in:available,maintenance',
            'description' => 'nullable|string|max:500',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $room->update([
            'floor_id' => $validated['floor_id'],
            'room_name' => $validated['room_name'],
            'capacity' => $validated['capacity'] ?? null,
            'status' => $this->resolveStatus($room, $validated['status']),
            'description' => $validated['description'] ?? null,
        ]);

        $room->facilities()->sync($validated['facilities'] ?? []);

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        if ($room->issues()->exists() || $room->maintenances()->exists()) {
            return redirect()
                ->route('classrooms.index')
                ->with('error', 'This room cannot be deleted because it has issue reports or maintenance history.');
        }

        if ($room->classSessions()->exists() || $room->roomSessions()->exists()) {
            return redirect()
                ->route('classrooms.index')
                ->with(
                    'error',
                    'This room cannot be deleted because it has class schedules or session history.'
                );
        }

        $room->facilities()->detach();

        $room->delete();

        return redirect()
            ->route('classrooms.index')
            ->with('success', 'Room deleted successfully.');
    }

    public function qr(Room $room)
    {
        $room->load([
            'floor.building.campus',
            'retiredQrCodes' => fn ($query) => $query->latest('retired_at'),
        ]);

        $qrData = $room->scanUrl();

        // localhost and Laragon's .test names only work on this computer.
        $qrHost = (string) parse_url($qrData, PHP_URL_HOST);
        $qrOnlyWorksOnThisComputer = in_array($qrHost, ['localhost', '127.0.0.1'], true)
            || str_ends_with($qrHost, '.test');

        return view('admin.rooms.qr', compact(
            'room',
            'qrData',
            'qrOnlyWorksOnThisComputer'
        ));
    }

    public function printQr(Room $room)
    {
        $room->load([
            'floor.building.campus',
        ]);

        $qrData = $room->scanUrl();

        return view('admin.rooms.qr-print', compact(
            'room',
            'qrData'
        ));
    }

    /**
     * Replace a damaged or compromised QR code. The old code stops working
     * immediately, so the new one must be printed and posted in the room.
     */
    public function regenerateQr(Room $room): RedirectResponse
    {
        $room->regenerateQrCode();

        return redirect()
            ->route('rooms.qr', $room)
            ->with('success', 'A new QR code was generated. Print it and replace the one posted in the room; the old QR code no longer works.');
    }

    /**
     * Occupied is set by starting and ending sessions, not by the admin form.
     * An admin can put a room under maintenance at any time; otherwise a room
     * with a running session stays occupied.
     */
    private function resolveStatus(Room $room, string $requestedStatus): string
    {
        if ($requestedStatus === 'maintenance') {
            return 'maintenance';
        }

        return $room->activeSession()->exists() ? 'occupied' : 'available';
    }
}
