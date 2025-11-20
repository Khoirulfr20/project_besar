<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\FaceDataController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    // Face Data Routes
    Route::prefix('face-data')->group(function () {
        Route::post('/', [FaceDataController::class, 'store']);
        Route::get('/user/{userId}', [FaceDataController::class, 'getUserFaces']);
        Route::post('/{id}/set-primary', [FaceDataController::class, 'setPrimary']);
        Route::delete('/{id}', [FaceDataController::class, 'destroy']);
    });

    // Schedule Routes
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::get('/my-schedules', [ScheduleController::class, 'mySchedules']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        
        // Admin & Pimpinan only
        Route::middleware('role:admin,pimpinan')->group(function () {
            Route::post('/', [ScheduleController::class, 'store']);
            Route::put('/{id}', [ScheduleController::class, 'update']);
            Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        });
    });

    // Attendance Routes
    Route::prefix('attendances')->group(function () {
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/my-attendance', [AttendanceController::class, 'myAttendance']);
        Route::get('/today', [AttendanceController::class, 'todayAttendance']);
        Route::get('/statistics', [AttendanceController::class, 'statistics']);
        
        // Admin & Pimpinan only
        Route::middleware('role:admin,pimpinan')->group(function () {
            Route::get('/', [AttendanceController::class, 'index']);
            Route::get('/{id}', [AttendanceController::class, 'show']);
            Route::put('/{id}/status', [AttendanceController::class, 'updateStatus']);
            Route::post('/{id}/approve', [AttendanceController::class, 'approve']);
            Route::get('/{id}/history', [AttendanceController::class, 'history']);
        });
    });

    // Report Routes (Pimpinan only)
    Route::prefix('reports')->middleware('role:pimpinan')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
        Route::post('/generate', [ReportController::class, 'generate']);
        Route::get('/summary', [ReportController::class, 'summary']);
        Route::get('/{id}/download', [ReportController::class, 'download']);
    });

    // ✅ FIX: User List untuk Pimpinan (untuk filter dropdown)
    Route::get('/users/list', [UserController::class, 'getUsersList'])
        ->middleware('role:admin,pimpinan');

    // User Management Routes (Admin only)
    Route::prefix('users')->middleware('role:admin')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    });

    // Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::get('/public', [SettingController::class, 'publicSettings']);
        
        // Admin only
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [SettingController::class, 'store']);
            Route::put('/{key}', [SettingController::class, 'update']);
            Route::delete('/{key}', [SettingController::class, 'destroy']);
        });
    });
});