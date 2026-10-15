<?php

use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FloorController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Faculty\ClassNotHeldController;
use App\Http\Controllers\Faculty\ClassroomSessionController;
use App\Http\Controllers\Faculty\ClassroomTransferController;
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\Faculty\RoomSelectionController;
use App\Http\Controllers\Faculty\TemporaryClassroomRequestController;
use App\Http\Controllers\Student\StudentDashboardController;
use Illuminate\Support\Facades\Route;

/* Authentication */
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/* Administrator */
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::resource('campuses', CampusController::class);

    Route::resource('buildings', BuildingController::class);

    Route::resource('floors', FloorController::class);

    Route::get(
        '/rooms/{room}/qr/print',
        [RoomController::class, 'printQr']
    )->name('rooms.qr.print');

    Route::get(
        '/rooms/{room}/qr',
        [RoomController::class, 'qr']
    )->name('rooms.qr');

    Route::resource('rooms', RoomController::class);

    Route::resource('facilities', FacilityController::class);

    Route::resource('faculties', FacultyController::class);

    Route::resource('subjects', SubjectController::class);

    Route::resource('sections', SectionController::class);

    Route::resource('schedules', ClassSessionController::class);

});

Route::middleware(['auth', 'faculty'])->prefix('faculty')->group(function () {
    /* Classroom sessions (RLNK-21, RLNK-22). Room QR codes open the scan page. */
    Route::get('/scan/{code}', [ClassroomSessionController::class, 'scan'])
        ->name('faculty.sessions.scan');

    Route::post('/sessions', [ClassroomSessionController::class, 'store'])
        ->name('faculty.sessions.store');

    Route::post('/sessions/{roomSession}/end', [ClassroomSessionController::class, 'end'])
        ->name('faculty.sessions.end');

    Route::get('/dashboard', [FacultyDashboardController::class, 'index'])
        ->name('faculty.dashboard');

    /* Classroom transfer (RLNK-24) */
    Route::get('/transfer', [ClassroomTransferController::class, 'create'])
        ->name('faculty.transfer.create');

    Route::post('/transfer', [ClassroomTransferController::class, 'store'])
        ->name('faculty.transfer.store');

    /* Temporary classroom requests (RLNK-23) */
    Route::get('/temporary-requests', [TemporaryClassroomRequestController::class, 'index'])
        ->name('faculty.temporary-requests.index');

    Route::get('/temporary-requests/create', [TemporaryClassroomRequestController::class, 'create'])
        ->name('faculty.temporary-requests.create');

    Route::post('/temporary-requests', [TemporaryClassroomRequestController::class, 'store'])
        ->name('faculty.temporary-requests.store');

    /* Manual room selection when a QR code can't be scanned (RLNK-18) */
    Route::get('/rooms', [RoomSelectionController::class, 'index'])
        ->name('faculty.rooms.index');

    /* Class not held (RLNK-25) */
    Route::get('/schedules/{classSession}/not-held', [ClassNotHeldController::class, 'create'])
        ->name('faculty.not-held.create');

    Route::post('/schedules/{classSession}/not-held', [ClassNotHeldController::class, 'store'])
        ->name('faculty.not-held.store');
});

Route::middleware(['auth', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])
        ->name('student.dashboard');
});
