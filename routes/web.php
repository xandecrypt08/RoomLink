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
use App\Http\Controllers\Faculty\FacultyDashboardController;
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
    Route::get('/dashboard', [FacultyDashboardController::class, 'index'])
        ->name('faculty.dashboard');

    Route::get('/temporary-requests', [TemporaryClassroomRequestController::class, 'index'])
        ->name('faculty.temporary-requests.index');

    Route::get('/temporary-requests/create', [TemporaryClassroomRequestController::class, 'create'])
        ->name('faculty.temporary-requests.create');

    Route::post('/temporary-requests', [TemporaryClassroomRequestController::class, 'store'])
        ->name('faculty.temporary-requests.store');
});

Route::middleware(['auth', 'student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])
        ->name('student.dashboard');
});
