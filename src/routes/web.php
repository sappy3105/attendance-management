<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

// ログイン後のみアクセス可能なグループ
Route::middleware('auth')->group(function () {
    // 打刻画面表示
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    // 出勤処理
    Route::post('/attendance/work-start', [AttendanceController::class, 'workStart']);

    //退勤処理
    Route::post('/attendance/work-end', [AttendanceController::class, 'workEnd']);

    //休憩入処理
    Route::post('/attendance/rest-start', [AttendanceController::class, 'restStart']);

    //休憩戻処理
    Route::post('/attendance/rest-end', [AttendanceController::class, 'restEnd']);

    //勤怠一覧表示
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
});
