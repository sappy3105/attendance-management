<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    // 勤怠一覧画面（管理者）
    public function list(Request $request)
    {
        // クエリパラメータから日付を取得、なければ今日の日付
        $dateStr = $request->query('date');
        $currentDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();

        // 前日と翌日の日付を計算
        $prevDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        // 指定した日の全ユーザーの勤怠データを取得
        // Eagerロードでユーザー情報も一緒に取得します
        $attendances = Attendance::with(['user', 'rests'])
            ->whereDate('date', $currentDate)
            ->get();

        return view('admin.attendance_list', [
            'attendances' => $attendances,
            'currentDate' => $currentDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
        ]);
    }

    // 勤怠詳細画面（管理者）
    public function showDetail($id)
    {
        // IDから勤怠データを取得（スタッフ情報も一緒に）
        $attendance = Attendance::with('user')->findOrFail($id);

        $user = $attendance->user;

        // ビューに $attendance と $user の両方を渡す
        return view('admin.attendance_detail', [
            'attendance' => $attendance,
            'user' => $user,
            // その他、休憩(rests)や申請中フラグ(isPending)などの変数も必要に応じて渡してください
            'rests' => $attendance->rests ?? collect(),
            'isPending' => false, // 仮のステータス
            'displayData' => [
                'check_in' => $attendance->check_in,
                'check_out' => $attendance->check_out,
                'remarks' => $attendance->remarks,
            ],
        ]);
    }

    // スタッフ一覧画面（管理者）
    public function staffList()
    {
        // roleが1（一般スタッフ）のユーザーのみ取得
        $users = User::where('role', 1)->get();

        return view('admin.staff_list', compact('users'));
    }

    // スタッフ別勤怠一覧画面
    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 表示月の判定（クエリパラメータ month がなければ今月）
        $monthStr = $request->query('month');
        $currentMonth = $monthStr ? Carbon::parse($monthStr)->startOfMonth() : Carbon::today()->startOfMonth();

        // 前月・翌月の計算
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // 指定スタッフの対象月の勤怠データを取得
        $attendances = Attendance::with('rests')
            ->where('user_id', $id)
            ->whereBetween('date', [$currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth()])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        // カレンダー作成（1日〜月末まで）
        $calendar = [];
        $daysInMonth = $currentMonth->daysInMonth;
        for ($i = 0; $i < $daysInMonth; $i++) {
            $calendar[] = $currentMonth->copy()->addDays($i);
        }

        return view('admin.staff_attendance_list', [
            'user' => $user,
            'calendar' => $calendar,
            'attendances' => $attendances,
            'currentMonth' => $currentMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }
}
