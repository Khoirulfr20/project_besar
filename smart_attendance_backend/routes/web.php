<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAttendanceFaceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root
Route::get('/', fn() => redirect()->route('admin.login'));

// ==================== ADMIN ROUTES ====================
Route::prefix('admin')->name('admin.')->group(function () {

    // ---------- GUEST (belum login) ----------
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    });

    // ---------- AUTHENTICATED ADMIN ----------
    Route::middleware(['auth'])->group(function () {
        // Logout
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // ==================== USERS ====================
       Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminController::class, 'usersIndex'])->name('index');
            Route::get('/create', [AdminController::class, 'usersCreate'])->name('create');
            Route::post('/', [AdminController::class, 'usersStore'])->name('store');
            Route::get('/{id}/edit', [AdminController::class, 'usersEdit'])->name('edit');
            Route::put('/{id}', [AdminController::class, 'usersUpdate'])->name('update');
            Route::delete('/{id}', [AdminController::class, 'usersDestroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [AdminController::class, 'usersToggleStatus'])->name('toggle-status');
        });
        
        // ==================== SCHEDULES ====================
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
            Route::put('/{id}/status', [AdminController::class, 'updateStatus'])->name('updateStatus'); // FIX
            Route::get('/{id}/history', [AdminController::class, 'attendancesHistory'])->name('history');
        });


        // ==================== RECORD MANUAL + FACE ====================
        Route::prefix('attendance')->name('attendance.')->group(function () {
            // Manual (punya kamu, biarkan)
            Route::get('/record', [AdminAttendanceFaceController::class, 'record'])->name('record');
            Route::post('/record', [AdminAttendanceFaceController::class, 'storeManual'])->name('storeManual');

            // Face Recognition (VIEW)
            Route::get('/face', [AdminAttendanceFaceController::class, 'faceRecord'])->name('face.record');

            // ✅ TAMBAHKAN INI - Route GET untuk VIEW halaman registrasi
            Route::get('/face/register', [AdminAttendanceFaceController::class, 'faceRegisterView'])
                ->name('face.register.view');
                
            // Face Recognition (AJAX from Blade)
            Route::post('/face/recognize', [AdminAttendanceFaceController::class, 'faceRecognize'])
                ->name('face.recognize');

            Route::post('/face/save-attendance', [AdminAttendanceFaceController::class, 'faceSaveAttendance'])
                ->name('face.saveAttendance');

            Route::post('/face/register', [AdminAttendanceFaceController::class, 'faceRegister'])
                ->name('face.register');

            // ✅ TAMBAHKAN INI - Route baru untuk check status
            Route::post('/face/check-status', [AdminAttendanceFaceController::class, 'checkAttendanceStatus'])
                ->name('face.checkStatus');

            // (kalau kamu masih pakai)
            Route::post('/bulk-import', [AdminController::class, 'bulkImportAttendance'])->name('bulkImport');

        });
        
        // ==================== REPORTS ====================
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/attendance', [AdminController::class, 'attendanceReport'])->name('attendance');
            Route::get('/export', [AdminController::class, 'exportReport'])->name('export');
        });

        // History + Settings
        Route::get('/history', [AdminController::class, 'historyIndex'])->name('history.index');
        Route::get('/settings', [AdminController::class, 'settingsIndex'])->name('settings.index');
        Route::put('/settings', [AdminController::class, 'settingsUpdate'])->name('settings.update');
        Route::post('/settings/clear-cache', [AdminController::class, 'settingsClearCache'])->name('settings.clear-cache');
    });
});
