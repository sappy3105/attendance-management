<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

// ログイン後のみアクセス可能なグループ
Route::middleware('auth')->group(function () {
    // 打刻画面表示
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    // 出勤処理
    Route::post('/attendance/work-start', [AttendanceController::class, 'workStart']);

    // 退勤処理
    Route::post('/attendance/work-end', [AttendanceController::class, 'workEnd']);

    // 休憩入処理
    Route::post('/attendance/rest-start', [AttendanceController::class, 'restStart']);

    // 休憩戻処理
    Route::post('/attendance/rest-end', [AttendanceController::class, 'restEnd']);

    // 勤怠一覧表示
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // 勤怠詳細画面表示
    Route::get('/attendance/{date}', [AttendanceController::class, 'showDetail'])
        ->name('attendance.detail');

    // 勤怠詳細の更新（修正申請）
    Route::post('/attendance/{date}', [AttendanceController::class, 'updateDetail'])
        ->name('attendance.update');

    // 申請一覧画面表示
    Route::get('/stamp_correction_request/list',[AttendanceController::class, 'showRequestList'])->name('attendance.requests');
});
