<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\ClassSession;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = Auth::user();

        $rooms = Room::with([
            'floor.building.campus',
            'facilities',
        ])
            ->orderBy('room_name')
            ->get();

        $totalRooms = $rooms->count();

        $availableRooms = $rooms
            ->where('status', 'available')
            ->count();

        $occupiedRooms = $rooms
            ->where('status', 'occupied')
            ->count();

        $maintenanceRooms = $rooms
            ->where('status', 'maintenance')
            ->count();

        return view('student.dashboard', compact(
            'student',
            'rooms',
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'maintenanceRooms'
        ));
    }
}