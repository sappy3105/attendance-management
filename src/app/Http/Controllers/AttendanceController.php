<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        // 今日の日付を取得
        $today = Carbon::today()->format('Y-m-d');

        // ログインユーザーの今日の勤怠レコードを1件取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', $today)
            ->first();

        return view('attendance', compact('attendance'));
    }
}
