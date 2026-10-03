<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Models\ClassSession;

class DashboardController extends Controller
{
    public function index()
    {
        // Infrastructure counts
        $totalCampuses = Campus::count();
        $totalBuildings = Building::count();
        $totalFloors = Floor::count();
        $totalRooms = Room::count();

        // Room status counts
        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        // Recent class sessions
        $recentSessions = ClassSession::with([
            'room.floor.building',
            'faculty',
            'subject',
            'section',
        ])
        ->orderBy('start_time')
        ->take(5)
        ->get();

        return view('admin.dashboard', compact(
            'totalCampuses',
            'totalBuildings',
            'totalFloors',
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'maintenanceRooms',
            'recentSessions'
        ));
    }
}