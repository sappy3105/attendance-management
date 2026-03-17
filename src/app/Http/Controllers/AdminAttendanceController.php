<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
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
            ->whereDate('date', $currentDate->format('Y-m-d'))
            ->get();

        return view('admin.attendance_list', [
            'attendances' => $attendances,
            'currentDate' => $currentDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
        ]);
    }

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
}
