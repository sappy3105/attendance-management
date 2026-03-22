<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

// トップページ（ログイン状態により分岐）
Route::get('/', function () {
    if (!Auth::check()) return view('auth.login');
    return Auth::user()->role === 2
        ? redirect()->route('admin.attendance.list')
        : redirect()->route('attendance.index');
});

// 管理者用ログイン画面
Route::get('/admin/login', fn() => view('admin.auth.login'))->name('admin.login');

// 管理者ログインの「実行処理」
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);

// ログイン後のみアクセス可能なグループ
Route::middleware('auth')->group(function () {
    // 打刻画面表示
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    // 出勤処理
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);

    // 退勤処理
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

    // 休憩入処理
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart']);

    // 休憩戻処理
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd']);

    // 勤怠一覧表示
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // 勤怠詳細画面表示
    Route::get('/attendance/{date}', [AttendanceController::class, 'showDetail'])
        ->name('attendance.detail');

    // 勤怠詳細の更新（修正申請）
    Route::post('/attendance/{date}', [AttendanceController::class, 'updateDetail'])
        ->name('attendance.update');

    // 申請一覧画面表示
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'showRequestList'])->name('attendance.requests');

    // 管理者用
    Route::prefix('admin')->group(function () {
        // 勤怠一覧画面表示
        Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('admin.attendance.list');

        // 勤怠詳細画面表示
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'showDetail'])->name('admin.attendance.detail');

        // 勤怠修正
        Route::post('/attendance/update/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

        // 申請一覧（管理者用：全ユーザーの申請が見れる）
        Route::get('/stamp_correction_request/list', [AdminAttendanceController::class, 'showRequestList'])->name('admin.attendance.requests');

        // スタッフ一覧表示
        Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('admin.staff.list');

        // スタッフ別勤怠一覧表示
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('admin.staff.attendance');

        // 修正申請承認画面の表示
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'showApprove'])
            ->name('admin.attendance.approve.show');

        // 承認処理
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'approve'])
            ->name('admin.attendance.approve');

    });
});
