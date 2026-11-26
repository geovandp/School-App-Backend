<?php

use App\Http\Controllers\Admin\AttendanceAdjustmentAdminController;
use App\Http\Controllers\Admin\AttendanceAdminController;
use App\Http\Controllers\Admin\AttendanceApprovalController;
use App\Http\Controllers\Admin\SchoolCalendarAdminController;
use App\Http\Controllers\Admin\WorkScheduleAdminController;
use App\Http\Controllers\Attendance\AttendanceAdjustmentController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Attendance\AttendanceRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Attendance Routes
|--------------------------------------------------------------------------
*/

// Group Presensi Pribadi Pegawai / Guru
Route::middleware('auth:sanctum')->prefix('attendance')->name('attendance.')->group(function () {
    // Sesi & Event Presensi
    Route::get('/today', [AttendanceController::class, 'today'])->name('today');
    Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
    Route::post('/temporary-check-out', [AttendanceController::class, 'temporaryCheckOut'])->name('temporary-check-out');
    Route::post('/return-check-in', [AttendanceController::class, 'returnCheckIn'])->name('return-check-in');
    Route::post('/final-check-out', [AttendanceController::class, 'finalCheckOut'])->name('final-check-out');
    Route::get('/history', [AttendanceController::class, 'history'])->name('history');
    Route::get('/sessions/{id}', [AttendanceController::class, 'sessionDetail'])->name('sessions.detail');
    Route::get('/recap', [AttendanceController::class, 'personalRecap'])->name('recap');

    // Permohonan Izin / Cuti / Sakit (Requests)
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [AttendanceRequestController::class, 'index'])->name('index');
        Route::post('/', [AttendanceRequestController::class, 'store'])->name('store');
        Route::get('/{id}', [AttendanceRequestController::class, 'show'])->name('show');
        Route::post('/{id}/cancel', [AttendanceRequestController::class, 'cancel'])->name('cancel');
    });

    // Pengajuan Koreksi Presensi (Adjustments)
    Route::prefix('adjustments')->name('adjustments.')->group(function () {
        Route::get('/', [AttendanceAdjustmentController::class, 'index'])->name('index');
        Route::post('/', [AttendanceAdjustmentController::class, 'store'])->name('store');
        Route::get('/{id}', [AttendanceAdjustmentController::class, 'show'])->name('show');
    });
});

// Group Administrasi Presensi (Admin / Kepala Sekolah)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Monitoring & Finalisasi Presensi
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/sessions', [AttendanceAdminController::class, 'sessions'])->name('sessions');
        Route::get('/sessions/{id}', [AttendanceAdminController::class, 'sessionDetail'])->name('sessions.detail');
        Route::get('/recap', [AttendanceAdminController::class, 'recap'])->name('recap');
        Route::get('/violations', [AttendanceAdminController::class, 'violations'])->name('violations');
        Route::post('/violations/{id}/review', [AttendanceAdminController::class, 'reviewViolation'])->name('violations.review');
        Route::post('/finalize', [AttendanceAdminController::class, 'finalize'])->name('finalize');
    });

    // Approval Permohonan Izin/Cuti
    Route::prefix('attendance-approvals')->name('attendance-approvals.')->group(function () {
        Route::get('/', [AttendanceApprovalController::class, 'index'])->name('index');
        Route::get('/{id}', [AttendanceApprovalController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AttendanceApprovalController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [AttendanceApprovalController::class, 'reject'])->name('reject');
    });

    // Approval Koreksi Presensi
    Route::prefix('attendance-adjustments')->name('attendance-adjustments.')->group(function () {
        Route::get('/', [AttendanceAdjustmentAdminController::class, 'index'])->name('index');
        Route::get('/{id}', [AttendanceAdjustmentAdminController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AttendanceAdjustmentAdminController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [AttendanceAdjustmentAdminController::class, 'reject'])->name('reject');
    });

    // Manajemen Jadwal Kerja
    Route::prefix('work-schedules')->name('work-schedules.')->group(function () {
        Route::get('/', [WorkScheduleAdminController::class, 'index'])->name('index');
        Route::post('/', [WorkScheduleAdminController::class, 'store'])->name('store');
        Route::get('/{id}', [WorkScheduleAdminController::class, 'show'])->name('show');
        Route::put('/{id}', [WorkScheduleAdminController::class, 'update'])->name('update');
    });

    // Penugasan Jadwal Pegawai
    Route::post('/employee-work-schedules', [WorkScheduleAdminController::class, 'assign'])
        ->name('employee-work-schedules.assign');

    // Manajemen Kalender Sekolah
    Route::prefix('school-calendar')->name('school-calendar.')->group(function () {
        Route::get('/', [SchoolCalendarAdminController::class, 'index'])->name('index');
        Route::post('/', [SchoolCalendarAdminController::class, 'store'])->name('store');
    });
});
