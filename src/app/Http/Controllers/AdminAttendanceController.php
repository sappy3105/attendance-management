<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use App\Models\RestCorrectRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /** 勤怠一覧画面（管理者） */
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

    /** 勤怠詳細画面（管理者） */
    public function showDetail($id)
    {
        // IDから勤怠データを取得（スタッフ情報も一緒に）
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);

        $pendingRequest = AttendanceCorrectRequest::where('attendance_id', $id)
            ->where('status', 1)
            ->first();

        $isPending = !is_null($pendingRequest);

        // 表示データの切り替え
        $displayData = [
            'check_in'  => $isPending ? $pendingRequest->check_in : $attendance->check_in,
            'check_out' => $isPending ? $pendingRequest->check_out : $attendance->check_out,
            'remarks'   => $isPending ? $pendingRequest->remarks : $attendance->remarks,
        ];

        $rests = $isPending
            ? RestCorrectRequest::where('attendance_correct_request_id', $pendingRequest->id)->get()
            : $attendance->rests;

        // ビューに $attendance と $user の両方を渡す
        return view('admin.attendance_detail', [
            'attendance' => $attendance,
            'user'  => $attendance->user,
            'rests' => $rests,
            'isPending'  => $isPending,
            'displayData' => $displayData,
        ]);
    }

    /** スタッフ一覧画面（管理者） */
    public function staffList()
    {
        // roleが1（一般スタッフ）のユーザーのみ取得
        $users = User::where('role', 1)->get();

        return view('admin.staff_list', compact('users'));
    }

    /** スタッフ別勤怠一覧画面  */
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

    /** 勤怠詳細の修正申請 (管理者用) */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {
            // 1. 勤怠修正申請テーブルに保存
            $correctRequest = AttendanceCorrectRequest::create([
                'attendance_id' => $attendance->id,
                'user_id'       => $attendance->user_id, // 対象スタッフのID
                'date'          => $attendance->date,
                'check_in'      => $request->check_in,
                'check_out'     => $request->check_out,
                'remarks'       => $request->remarks,
                'status'        => 1, // 承認待ち
            ]);

            // 2. 休憩修正申請テーブルに保存
            if ($request->has('break_start')) {
                foreach ($request->break_start as $index => $start) {
                    $end = $request->break_end[$index] ?? null;
                    if ($start || $end) {
                        RestCorrectRequest::create([
                            'attendance_correct_request_id' => $correctRequest->id,
                            'break_start' => $start,
                            'break_end'   => $end,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.attendance.detail', ['id' => $id]);
    }

    /** 申請一覧画面の表示 (管理者用) */
    public function showRequestList(Request $request)
    {
        $statusMode = $request->query('status', 'pending');
        $statusCode = ($statusMode === 'approved') ? 2 : 1;

        // 管理者は「全ユーザー」の申請を取得する
        $requests = AttendanceCorrectRequest::with('user')
            ->where('status', $statusCode)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.attendance_request_list', [
            'requests' => $requests,
            'status' => $statusMode,
        ]);
    }

    /** 修正申請承認画面の表示 */
    public function showApprove($attendance_correct_request_id)
    {
        // 申請データを取得（リレーションでユーザーと休憩申請も取得）
        $correctRequest = AttendanceCorrectRequest::with(['user', 'restCorrectRequests'])
            ->findOrFail($attendance_correct_request_id);

        return view('admin.attendance_approve', [
            'correctRequest' => $correctRequest,
            'user' => $correctRequest->user,
        ]);
    }

    /** 承認処理の実行 */
    public function approve($attendance_correct_request_id)
    {
        $correctRequest = AttendanceCorrectRequest::findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($correctRequest) {
            // 1. 本番の勤怠レコード(attendances)を更新
            $attendance = Attendance::findOrFail($correctRequest->attendance_id);
            $attendance->update([
                'check_in'  => $correctRequest->check_in,
                'check_out' => $correctRequest->check_out,
                'remarks'   => $correctRequest->remarks,
            ]);

            // 2. 本番の休憩レコード(rests)を更新
            // 一度削除して、申請された内容で作り直すのが確実です
            Rest::where('attendance_id', $attendance->id)->delete();
            foreach ($correctRequest->restCorrectRequests as $restRequest) {
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'break_start'   => $restRequest->break_start,
                    'break_end'     => $restRequest->break_end,
                ]);
            }

            // 3. 申請ステータスを「承認済み(2)」に変更
            $correctRequest->update(['status' => 2]);
        });

        return redirect()->back()->with('message', '承認しました');
    }
}
