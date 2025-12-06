<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\FaceDataController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;

// ---------- PUBLIC ----------
Route::post('/login', [AuthController::class, 'login']);

// ---------- PROTECTED (SANCTUM) ----------
Route::middleware('auth:sanctum')->group(function () {

    // User Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Face Data
    Route::prefix('face')->group(function () {
        Route::post('/', [FaceDataController::class, 'store']);
        Route::get('/user/{userId}', [FaceDataController::class, 'getUserFaces']);
        Route::post('/set-primary/{id}', [FaceDataController::class, 'setPrimary']);
        Route::delete('/{id}', [FaceDataController::class, 'destroy']);
    });

    // Schedules
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/my', [ScheduleController::class, 'mySchedules']);
    Route::get('/schedules/{id}', [ScheduleController::class, 'show']);

    Route::middleware('role:admin,pimpinan')->group(function () {
        Route::post('/schedules', [ScheduleController::class, 'store']);
        Route::put('/schedules/{id}', [ScheduleController::class, 'update']);
        Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy']);
    });

    // Attendance
    Route::prefix('attendance')->group(function () {
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/my', [AttendanceController::class, 'myAttendance']);
        Route::get('/today', [AttendanceController::class, 'todayAttendance']);
        Route::get('/statistics', [AttendanceController::class, 'statistics']);

        Route::middleware('role:admin,pimpinan')->group(function () {
            Route::get('/', [AttendanceController::class, 'index']);
            Route::get('/{id}', [AttendanceController::class, 'show']);
            Route::put('/status/{id}', [AttendanceController::class, 'updateStatus']);
            Route::post('/approve/{id}', [AttendanceController::class, 'approve']);
            Route::get('/history/{id}', [AttendanceController::class, 'history']);
        });
    });

    // Users list (for dropdown)
    Route::get('/users/list', [UserController::class, 'getUsersList'])
        ->middleware('role:admin,pimpinan');

    // User Management (Admin only)
    Route::middleware('role:admin')->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/status/{id}', [UserController::class, 'toggleStatus']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/read/{id}', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Settings (only admin can update)
    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/settings/public', [SettingController::class, 'publicSettings']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/settings', [SettingController::class, 'store']);
        Route::put('/settings/{key}', [SettingController::class, 'update']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);
    });
});
