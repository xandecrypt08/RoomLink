<?php

use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\CollegeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FloorController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SubjectImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Faculty\ClassNotHeldController;
use App\Http\Controllers\Faculty\ClassroomSessionController;
use App\Http\Controllers\Faculty\ClassroomTransferController;
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\Faculty\NotificationController;
use App\Http\Controllers\Faculty\RoomSelectionController;
use App\Http\Controllers\Faculty\TemporaryClassroomRequestController;
use App\Http\Controllers\Faculty\TemporaryClassroomRequestDecisionController;
use App\Http\Controllers\FacultyLocatorController;
use App\Http\Controllers\ProfileController;
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

/* Faculty locator (RLNK-29), open to every signed-in role */
Route::get('/faculty-locator', [FacultyLocatorController::class, 'index'])
    ->middleware('auth')
    ->name('faculty-locator.index');

/* My Profile, for every role. What can be changed depends on the role (User::editableProfileFields). */
Route::middleware('auth')->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::get('/photos/{user}', [ProfileController::class, 'photo'])->name('profile.photo');
});

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

    Route::post(
        '/rooms/{room}/qr/regenerate',
        [RoomController::class, 'regenerateQr']
    )->name('rooms.qr.regenerate');

    Route::resource('rooms', RoomController::class);

    Route::resource('facilities', FacilityController::class);

    Route::resource('faculties', FacultyController::class);

    /* Import subjects from a curriculum document. Declared before the resource so "import" isn't read as a subject. */
    Route::get('/subjects/import', [SubjectImportController::class, 'create'])
        ->name('subjects.import.create');

    Route::post('/subjects/import/preview', [SubjectImportController::class, 'preview'])
        ->name('subjects.import.preview');

    Route::post('/subjects/import', [SubjectImportController::class, 'store'])
        ->name('subjects.import.store');

    Route::resource('subjects', SubjectController::class);

    Route::resource('programs', ProgramController::class)->except('show');

    Route::resource('colleges', CollegeController::class)->except('show');

    Route::resource('sections', SectionController::class);

    Route::resource('schedules', ClassSessionController::class);

    /* User accounts (RLNK-208). Accounts are deactivated, never deleted. */
    Route::patch('/users/{user}/password', [UserController::class, 'resetPassword'])
        ->name('users.password');

    Route::patch('/users/{user}/active', [UserController::class, 'toggleActive'])
        ->name('users.active');

    Route::resource('users', UserController::class)->except(['show', 'destroy']);

});

Route::middleware(['auth', 'faculty'])->prefix('faculty')->group(function () {
    /* In-app QR scanner: reads the room code from a photo (public/js/qr-scanner.js) */
    Route::view('/scanner', 'faculty.scanner')
        ->name('faculty.scanner');

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

    /* Approve or reject a temporary classroom request (RLNK-27/28) */
    Route::post('/temporary-requests/{temporaryClassroomRequest}/approve', [TemporaryClassroomRequestDecisionController::class, 'approve'])
        ->name('faculty.temporary-requests.approve');

    Route::post('/temporary-requests/{temporaryClassroomRequest}/reject', [TemporaryClassroomRequestDecisionController::class, 'reject'])
        ->name('faculty.temporary-requests.reject');

    /* Notifications (RLNK-30) */
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('faculty.notifications.index');

    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])
        ->name('faculty.notifications.read');

    /* Manual room selection when a QR code can't be scanned (RLNK-18) */
    Route::get('/rooms', [RoomSelectionController::class, 'index'])
        ->name('faculty.rooms.index');

    /* Starting without the QR code needs a reason and is flagged for the admin */
    Route::get('/rooms/{room}/start', [ClassroomSessionController::class, 'manual'])
        ->name('faculty.rooms.start');

    Route::post('/rooms/{room}/start', [ClassroomSessionController::class, 'storeManual'])
        ->name('faculty.rooms.start.store');

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
