<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminRequestController;
use Laravel\Fortify\Http\Requests\LoginRequest;


Route::get('/admin/login', function () {
    return view('auth.admin_login');
})->name('admin.login');

Route::post('/admin/login', function (LoginRequest $request) {
    $request->merge(['admin' => 1]);
    return app(\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class)
        ->store($request);
});

Route::post('/admin/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/admin/login');
})->name('admin.logout');


Route::middleware(['auth', 'verified', 'role:0'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    Route::post('/attendance/start', [AttendanceController::class, 'startWork'])
        ->name('attendance.start');

    Route::post('/attendance/end', [AttendanceController::class, 'endWork'])
        ->name('attendance.end');

    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart'])
        ->name('attendance.break.start');

    Route::post('/attendance/break/end', [AttendanceController::class, 'breakEnd'])
        ->name('attendance.break.end');

    Route::get('/attendance/list', [AttendanceController::class, 'monthlyList'])
        ->name('attendance.monthly_list');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])
        ->name('attendance.detail');

    Route::post('/attendance/request/{id}', [RequestController::class, 'store'])
        ->name('attendance.request');
});


Route::middleware(['auth', 'role:1'])->group(function () {

    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'dailyList'])
        ->name('admin.attendance.daily_list');

    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'detail'])
        ->name('admin.attendance.detail');

    Route::post('/admin/attendance/update/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff.list');

    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staffMonthlyList'])
        ->name('admin.attendance.staff');

    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'show'])
        ->name('admin.request.show');

    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'approve'])
        ->name('admin.request.approve');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])
        ->name('request.list');
});

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/login')->with('message', 'メール認証が完了しました');
})->middleware(['auth', 'signed'])->name('verification.verify');
