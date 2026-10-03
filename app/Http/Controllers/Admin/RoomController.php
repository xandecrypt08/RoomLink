<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Floor;
use App\Models\Facility;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $rooms = Room::with([
            'floor.building.campus',
            'facilities',
        ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('room_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('floor', function ($floorQuery) use ($search) {
                            $floorQuery->where(
                                'floor_number',
                                'like',
                                "%{$search}%"
                            );
                        })
                        ->orWhereHas('floor.building', function ($buildingQuery) use ($search) {
                            $buildingQuery
                                ->where('building_name', 'like', "%{$search}%")
                                ->orWhere('building_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('room_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.rooms.index', compact(
            'rooms',
            'search'
        ));
    }

    public function create()
    {
        $floors = Floor::with('building.campus')
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->get();

        $facilities = Facility::orderBy('facility_name')->get();

        return view('admin.rooms.create', compact(
            'floors',
            'facilities'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'room_name' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|in:available,occupied,maintenance',
            'description' => 'nullable|string|max:500',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $room = Room::create([
            'floor_id' => $validated['floor_id'],
            'room_name' => $validated['room_name'],
            'capacity' => $validated['capacity'] ?? null,
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
        ]);

        $room->facilities()->sync($validated['facilities'] ?? []);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room created successfully.');
    }

    public function show(Room $room)
    {
        $room->load([
            'floor.building.campus',
            'facilities',
            'classSessions.faculty',
            'classSessions.subject',
            'classSessions.section',
        ]);

        return view('admin.rooms.show', compact('room'));
    }

    public function edit(Room $room)
    {
        $floors = Floor::with('building.campus')
            ->orderBy('building_id')
            ->orderBy('floor_number')
            ->get();

        $facilities = Facility::orderBy('facility_name')->get();

        $room->load('facilities');

        return view('admin.rooms.edit', compact(
            'room',
            'floors',
            'facilities'
        ));
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'room_name' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|in:available,occupied,maintenance',
            'description' => 'nullable|string|max:500',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $room->update([
            'floor_id' => $validated['floor_id'],
            'room_name' => $validated['room_name'],
            'capacity' => $validated['capacity'] ?? null,
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
        ]);

        $room->facilities()->sync($validated['facilities'] ?? []);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        if ($room->classSessions()->exists()) {
            return redirect()
                ->route('rooms.index')
                ->with(
                    'error',
                    'This room cannot be deleted because it has class sessions.'
                );
        }

        $room->facilities()->detach();

        $room->delete();

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room deleted successfully.');
    }

    public function qr(Room $room)
    {
        $room->load('floor.building.campus');

        $qrData = route('rooms.qr', $room);

        return view('admin.rooms.qr', compact(
            'room',
            'qrData'
        ));
    }

    public function printQr(Room $room)
    {
        $room->load([
            'floor.building.campus',
        ]);

        $qrData = route('rooms.qr', $room);

        return view('admin.rooms.qr-print', compact(
            'room',
            'qrData'
        ));
    }
}