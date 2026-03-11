<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    //勤務外
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

    //出勤ボタン押下
    public function workStart(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->format('H:i:s');

        // 二重打刻防止（今日のデータが既にないか確認）
        $exists = Attendance::where('user_id', $userId)->where('date', $today)->exists();

        if (!$exists) {
            Attendance::create([
                'user_id' => $userId,
                'date' => $today,
                'check_in' => $now,
                'status' => 2, // 2:出勤中
            ]);
        }

        return redirect()->back();
    }

    //休憩入ボタン押下
    public function restStart(Request $request)
    {
        $userId = Auth::id();
        $today = \Carbon\Carbon::today()->format('Y-m-d');

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            // 1. restsテーブルに開始時刻を保存
            Rest::create([
                'attendance_id' => $attendance->id,
                'start_time' => \Carbon\Carbon::now()->format('H:i:s'),
            ]);

            // 2. attendancesテーブルのステータスを「休憩中」に更新
            $attendance->update([
                'status' => 3, // 3: 休憩中
            ]);
        }

        return redirect()->back();
    }

    //休憩戻ボタン押下
    public function restEnd(Request $request)
    {
        $userId = Auth::id();
        $today = \Carbon\Carbon::today()->format('Y-m-d');

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            // 1. end_time が null の最新の休憩レコードを1件取得
            $rest = Rest::where('attendance_id', $attendance->id)
                ->whereNull('end_time')
                ->latest()
                ->first();

            if ($rest) {
                // 2. 休憩終了時刻を保存
                $rest->update([
                    'end_time' => \Carbon\Carbon::now()->format('H:i:s'),
                ]);

                // 3. 勤怠ステータスを「出勤中」に戻す
                $attendance->update([
                    'status' => 2, // 2: 出勤中
                ]);
            }
        }

        return redirect()->back();
    }

    //退勤ボタン押下
    public function workEnd(Request $request)
    {
        $userId = Auth::id();
        $today = \Carbon\Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            // 退勤時刻を保存し、ステータスを「4: 退勤済」に更新
            $attendance->update([
                'check_out' => \Carbon\Carbon::now()->format('H:i:s'),
                'status' => 4, // 4: 退勤済
            ]);
        }

        return redirect()->back();
    }

    //勤怠一覧表示
    public function list(Request $request)
    {
        // クエリパラメータから年月を取得（なければ当月）
        $monthParam = $request->query('month', now()->format('Y-m'));
        $date = \Carbon\Carbon::parse($monthParam)->startOfMonth();

        // 1ヶ月分の全日付を生成
        $daysInMonth = $date->daysInMonth;
        $calendar = [];
        for ($i = 0; $i < $daysInMonth; $i++) {
            $calendar[] = $date->copy()->addDays($i);
        }

        // DBからログインユーザーの該当月のデータを取得
        $attendances = Attendance::where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get()
            ->keyBy('date'); // 日付をキーにして検索しやすくする

        return view('attendance_list', [
            'calendar' => $calendar,
            'attendances' => $attendances,
            'currentMonth' => $date,
            'prevMonth' => $date->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
