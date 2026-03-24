<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use App\Models\RestCorrectRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /** 勤務外 */
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

    /** 出勤ボタン押下 */
    public function checkIn(Request $request)
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

    /** 休憩入ボタン押下 */
    public function breakStart(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($attendance && $attendance->status === 2) {
            // 1. restsテーブルに開始時刻を保存
            Rest::create([
                'attendance_id' => $attendance->id,
                'break_start' => Carbon::now()->format('H:i:s'),
            ]);

            // 2. attendancesテーブルのステータスを「休憩中」に更新
            $attendance->update([
                'status' => 3, // 3: 休憩中
            ]);
        } else {
            // 出勤中ではないのに休憩ボタンを押された場合の処理を追加すると親切です
            return redirect()->back();
        }
    }

    /** 休憩戻ボタン押下 */
    public function breakEnd(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            // 1. break_end が null の最新の休憩レコードを1件取得
            $rest = Rest::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->orderBy('break_start', 'desc') // 開始が一番新しいものを取得
                ->first();

            if ($rest) {
                // 2. 休憩終了時刻を保存
                $rest->update([
                    'break_end' => Carbon::now()->format('H:i:s'),
                ]);

                // 3. 勤怠ステータスを「出勤中」に戻す
                $attendance->update([
                    'status' => 2, // 2: 出勤中
                ]);
            }
        }

        return redirect()->back();
    }

    /** 退勤ボタン押下 */
    public function checkOut(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            // 退勤時刻を保存し、ステータスを「4: 退勤済」に更新
            $attendance->update([
                'check_out' => Carbon::now()->format('H:i:s'),
                'status' => 4, // 4: 退勤済
            ]);
        }

        return redirect()->back();
    }

    /** 勤怠一覧表示 */
    public function list(Request $request)
    {
        // クエリパラメータから年月を取得（なければ当月）
        $monthParam = $request->query('month', now()->format('Y-m'));
        $date = Carbon::parse($monthParam)->startOfMonth();

        // 1ヶ月分の全日付を生成
        $daysInMonth = $date->daysInMonth;
        $calendar = [];
        for ($i = 0; $i < $daysInMonth; $i++) {
            $calendar[] = $date->copy()->addDays($i);
        }

        // DBからログインユーザーの該当月のデータを取得
        $attendances = Attendance::with('rests')
            ->where('user_id', Auth::id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get()
            ->keyBy(function ($item) {
                // もしモデルの $casts で 'date' => 'date' となっていれば、$item->date は Carbon インスタンス
                return ($item->date instanceof \Carbon\Carbon)
                    ? $item->date->format('Y-m-d')
                    : Carbon::parse($item->date)->format('Y-m-d');
            });

        return view('attendance_list', [
            'calendar' => $calendar,
            'attendances' => $attendances,
            'currentMonth' => $date,
            'prevMonth' => $date->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /** 勤怠詳細画面の表示 */
    public function showDetail($date)
    {
        $user = Auth::user(); // 現在のログインユーザー
        $carbonDate = Carbon::parse($date);

        // 1. 元の勤怠データを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $carbonDate)
            ->firstOrFail();

        // 2. この日の「承認待ち」の修正申請があるか確認
        $pendingRequest = null;
        if ($attendance) {
            $pendingRequest = AttendanceCorrectRequest::where('attendance_id', $attendance->id)
                ->where('status', 1) // 1:承認待ち
                ->with('restCorrectRequests') // 休憩の申請データも一緒に取得
                ->first();
        }

        // 3. 画面に表示する値を決定（申請中なら申請データ、なければ元のデータ）
        $displayData = [
            'check_in'  => $pendingRequest ? $pendingRequest->check_in : ($attendance ? $attendance->check_in : null),
            'check_out' => $pendingRequest ? $pendingRequest->check_out : ($attendance ? $attendance->check_out : null),
            'remarks'   => $pendingRequest ? $pendingRequest->remarks : ($attendance ? $attendance->remarks : ''),
        ];

        // 4. 休憩データ
        if ($pendingRequest) {
            // 申請中の休憩データをそのまま取得（$castsによって各要素は既にCarbonになっています）
            $rests = $pendingRequest->restCorrectRequests;
        } else {
            // 元の休憩データ
            $rests = $attendance ? $attendance->rests : collect();
        }

        return view('attendance_detail', [
            'user' => $user,
            'attendance' => $attendance,
            'date' => $date,
            'displayData' => $displayData,
            'rests' => $rests,
            'isPending' => !is_null($pendingRequest),
        ]);
    }

    /** 勤怠詳細の修正依頼 */
    public function updateDetail(AttendanceUpdateRequest $request, $date)
    {
        $user = Auth::user();
        $carbonDate = Carbon::parse($date);

        // 元となる勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $carbonDate)
            ->firstOrFail();

        $existsPending = AttendanceCorrectRequest::where('attendance_id', $attendance->id)
            ->where('status', 1)
            ->exists();

        if ($existsPending) {
            return redirect()->back()->withErrors(['already_pending' => '既に修正申請を提出済みです。承認されるまでお待ちください。']);
        }

        DB::transaction(function () use ($request, $user, $carbonDate, $attendance) {

            // 1. 勤怠修正申請の作成（マイグレーションのカラム名に厳密に合わせました）
            $correctRequest = AttendanceCorrectRequest::create([
                'attendance_id' => $attendance->id,
                'user_id'       => $user->id,
                'date'          => $carbonDate->format('Y-m-d'),
                'check_in'      => $request->check_in,  // Blade側のnameをcheck_inにする想定
                'check_out'     => $request->check_out, // Blade側のnameをcheck_outにする想定
                'remarks'       => $request->remarks,   // noteではなくremarks
                'status'        => 1, // 1:承認待ち
            ]);

            // 2. 休憩修正申請の作成
            if ($request->has('break_start')) {
                foreach ($request->break_start as $index => $start) {
                    $end = $request->break_end[$index] ?? null;

                    // 開始・終了の両方が入力されている場合のみ保存
                    if (!is_null($start) && $start !== '' && !is_null($end) && $end !== '') {
                        RestCorrectRequest::create([
                            'attendance_correct_request_id' => $correctRequest->id,
                            'break_start' => $start,
                            'break_end'   => $end,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('attendance.detail', ['date' => $date]);
    }

    /** 申請一覧画面の表示 */
    public function showRequestList(Request $request)
    {
        $user = Auth::user();

        // クエリパラメータから表示モードを取得（デフォルトは承認待ち：pending）
        $statusMode = $request->query('status', 'pending');
        $statusId = ($statusMode === 'approved') ? 2 : 1;

        $requests = AttendanceCorrectRequest::where('user_id', $user->id)
            ->where('status', $statusId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('attendance_request_list', [
            'user' => $user,
            'requests' => $requests,
            'status' => $statusMode,
        ]);
    }
}
