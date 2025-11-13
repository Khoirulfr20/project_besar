<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root ke halaman login admin
Route::get('/', function () {
    return redirect()->route('admin.login');
});

// ==================== ADMIN ROUTES ====================
Route::prefix('admin')->name('admin.')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Guest routes (belum login)
    |--------------------------------------------------------------------------
    */
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Authenticated admin routes (sudah login)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])->group(function () {
        // Logout
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // ==================== USERS MANAGEMENT ====================
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminController::class, 'usersIndex'])->name('index');
            Route::get('/create', [AdminController::class, 'usersCreate'])->name('create');
            Route::post('/', [AdminController::class, 'usersStore'])->name('store');
            Route::get('/{id}/edit', [AdminController::class, 'usersEdit'])->name('edit');
            Route::put('/{id}', [AdminController::class, 'usersUpdate'])->name('update');
            Route::delete('/{id}', [AdminController::class, 'usersDestroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [AdminController::class, 'usersToggleStatus'])->name('toggle-status');
        });
        
        // ==================== SCHEDULES MANAGEMENT ====================
        Route::prefix('schedules')->name('schedules.')->group(function () {
            Route::get('/', [AdminController::class, 'schedulesIndex'])->name('index');
            Route::get('/create', [AdminController::class, 'schedulesCreate'])->name('create');
            Route::post('/', [AdminController::class, 'schedulesStore'])->name('store');
            Route::get('/{id}/edit', [AdminController::class, 'schedulesEdit'])->name('edit');
            Route::put('/{id}', [AdminController::class, 'schedulesUpdate'])->name('update');
            Route::delete('/{id}', [AdminController::class, 'schedulesDestroy'])->name('destroy');
        });
        
        // ==================== ATTENDANCES MANAGEMENT ====================
        Route::prefix('attendances')->name('attendances.')->group(function () {
            Route::get('/', [AdminController::class, 'attendancesIndex'])->name('index');
            Route::put('/{id}/status', [AdminController::class, 'attendancesUpdateStatus'])->name('updateStatus');
            Route::get('/{id}/history', [AdminController::class, 'attendancesHistory'])->name('history');
        });
        
        // ==================== RECORD ATTENDANCE (NEW) ====================
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/record', [AdminController::class, 'recordAttendance'])->name('record');
            Route::post('/record', [AdminController::class, 'storeAttendance'])->name('store');
            Route::post('/bulk-import', [AdminController::class, 'bulkImportAttendance'])->name('bulkImport');
        });
        
        // ==================== HISTORY ====================
        Route::get('/history', [AdminController::class, 'historyIndex'])->name('history.index');
        
        // ==================== SETTINGS ====================
        Route::get('/settings', [AdminController::class, 'settingsIndex'])->name('settings.index');
        Route::put('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');
        Route::post('/settings/clear-cache', [AdminController::class, 'settingsClearCache'])->name('settings.clear-cache');
    });
});