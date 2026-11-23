<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Classroom Management on one page: every campus with its buildings, floors
 * and rooms, plus the facilities. Adding, editing and deleting are handled by
 * the controller of each record, which return here.
 */
class ClassroomController extends Controller
{
    public function index(Request $request): View
    {
        $campuses = Campus::with([
            'buildings' => fn ($query) => $query->orderBy('building_name'),
            'buildings.floors' => fn ($query) => $query->orderBy('floor_number'),
            'buildings.floors.rooms' => fn ($query) => $query->orderBy('room_name'),
            'buildings.floors.rooms.facilities' => fn ($query) => $query->orderBy('facility_name'),
        ])->orderBy('campus_name')->get();

        return view('admin.classrooms.index', [
            'campuses' => $campuses,
            'buildings' => $campuses->flatMap(fn (Campus $campus) => $campus->buildings),
            'floors' => $campuses->flatMap(fn (Campus $campus) => $campus->buildings->flatMap(fn (Building $building) => $building->floors)),
            'facilities' => Facility::withCount('rooms')->orderBy('facility_name')->get(),
            'editRoomId' => $request->integer('edit') ?: null,
        ]);
    }
}
