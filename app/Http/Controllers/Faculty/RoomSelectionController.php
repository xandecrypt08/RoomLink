<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manual room selection (RLNK-18): when a room's QR code cannot be scanned,
 * the faculty member finds the room here and continues to the same
 * verification page the QR code opens.
 */
class RoomSelectionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $rooms = Room::with('floor.building.campus')
            ->whereNotNull('qr_code')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('room_name', 'like', "%{$search}%")
                        ->orWhereHas('floor.building', function ($buildingQuery) use ($search) {
                            $buildingQuery->where('building_name', 'like', "%{$search}%")
                                ->orWhere('building_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('room_name')
            ->paginate(15)
            ->withQueryString();

        return view('faculty.rooms.index', compact('rooms', 'search'));
    }
}
