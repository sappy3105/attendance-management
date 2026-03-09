<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

// ログイン後のみアクセス可能なグループ
Route::middleware('auth')->group(function () {
    // 打刻画面（勤怠一覧）
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
});
