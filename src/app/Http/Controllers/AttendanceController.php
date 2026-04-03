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
    /** 勤怠画面表示(勤務外) */
    public function index()
    {
        // 今日の日付を取得
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        // ログインユーザーの今日の勤怠レコードを1件取得
        $attendance = $user->attendances()->where('date', $today)->first();

        return view('attendance', compact('attendance'));
    }

    /** 出勤ボタン押下 */
    public function checkIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        // 二重打刻防止（今日のデータが既にないか確認）
        $exists = $user->attendances()->where('date', $today)->exists();

        if (!$exists) {
            $user->attendances()->create([
                'date'     => $today,
                'check_in' => Carbon::now()->format('H:i:s'),
                'status'   => 1,
            ]);
        }

        return redirect()->back();
    }

    /** 休憩入ボタン押下 */
    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        $attendance = $user->attendances()->where('date', $today)->first();

        if ($attendance && $attendance->status === 1) {
            DB::transaction(function () use ($attendance) {
                // 1. restsテーブルに開始時刻を保存
                $attendance->rests()->create([
                    'break_start' => Carbon::now()->format('H:i:s'),
                ]);

                // 2. attendancesテーブルのステータスを「休憩中」に更新
                $attendance->update(['status' => 2]); // 2:休憩中
            });
        }
        return redirect()->back();
    }

    /** 休憩戻ボタン押下 */
    public function breakEnd(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        $attendance = $user->attendances()->where('date', $today)->first();

        if ($attendance && $attendance->status === 2) {
            // 1. break_end が null の最新の休憩レコードを1件取得
            $rest = $attendance->rests()
                ->whereNull('break_end')
                ->latest('break_start') // 開始が一番新しいものを取得
                ->first();

            if ($rest) {
                DB::transaction(function () use ($attendance, $rest) {
                    // 2. 休憩終了時刻を保存
                    $rest->update([
                        'break_end' => Carbon::now()->format('H:i:s'),
                    ]);

                    // 3. 勤怠ステータスを「出勤中」に戻す
                    $attendance->update(['status' => 1]); // 1:出勤中に戻す
                });
            }
        }

        return redirect()->back();
    }

    /** 退勤ボタン押下 */
    public function checkOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        $attendance = $user->attendances()->where('date', $today)->first();

        if ($attendance && $attendance->status === 1) {
            // 退勤時刻を保存し、ステータスを「4: 退勤済」に更新
            $attendance->update([
                'check_out' => Carbon::now()->format('H:i:s'),
                'status'    => 3, // 3: 退勤済
            ]);
        }

        return redirect()->back();
    }

    /** 勤怠一覧表示 */
    public function list(Request $request)
    {
        // 1. クエリパラメータから年月を取得（Carbonインスタンスとして生成）
        $monthParam = $request->query('month', now()->format('Y-m'));
        $date = Carbon::parse($monthParam)->startOfMonth();

        // 2. リレーションを活用してログインユーザーの該当月のデータを取得
        $attendances = Auth::user()->attendances()
            ->with('rests')
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get()
            ->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // 3. 1ヶ月分の全日付を生成
        $calendar = [];
        $daysInMonth = $date->daysInMonth;
        for ($i = 0; $i < $daysInMonth; $i++) {
            $calendar[] = $date->copy()->addDays($i);
        }

        return view('attendance_list', [
            'calendar'     => $calendar,
            'attendances'  => $attendances,
            'currentMonth' => $date,
            'prevMonth'    => $date->copy()->subMonth()->format('Y-m'),
            'nextMonth'    => $date->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /** 勤怠詳細画面の表示 */
    public function showDetail($date)
    {
        $user = Auth::user(); // 現在のログインユーザー

        // 1. 元の勤怠データを取得
        $attendance = $user->attendances()
            ->whereDate('date', $date)
            ->with('rests')
            ->first();

        // 2. この勤怠に対して「承認待ち」の修正申請があるか確認
        $pendingRequest = $attendance?->correctRequests()->where('status', 1)->first();

        $isPending = !is_null($pendingRequest);

        // 3. 画面に表示する値を決定（申請中なら申請データ、なければ元のデータ）
        $displayData = [
            'check_in'  => $isPending ? $pendingRequest->check_in : ($attendance?->check_in),
            'check_out' => $isPending ? $pendingRequest->check_out : ($attendance?->check_out),
            'remarks'   => $isPending ? $pendingRequest->remarks : ($attendance?->remarks),
        ];

        // 4. 休憩データの切り替え
        if ($isPending) {
            $rests = $pendingRequest->restCorrectRequests; // 申請中の休憩データ
        } else {
            $rests = $attendance ? $attendance->rests : collect(); // 元の休憩データ
        }


        $date = Carbon::parse($date); // ここで日付をCarbon化しておく

        return view('attendance_detail', compact(
            'attendance',
            'user',
            'isPending',
            'displayData',
            'rests',
            'date' // $attendanceがnullでもこれがあれば日付が表示できる
        ));
    }

    /** 勤怠詳細の修正依頼 */
    public function updateDetail(AttendanceUpdateRequest $request, $date)
    {
        $user = Auth::user();

        // 1.元となる勤怠レコードを取得。データがなければ、「退勤済」の土台を作成
        $attendance = $user->attendances()->firstOrCreate(
            ['date' => $date],
            ['status' => 3] // 退勤済
        );

        // 2. リレーションを使用して「承認待ち」の存在確認
        $existsPending = $attendance->correctRequests()
            ->where('status', 1)
            ->exists();

        if ($existsPending) {
            return redirect()->back()
                ->withInput() // 入力内容を保持
                ->withErrors(['already_pending' => '既に修正申請を提出済みです。承認されるまでお待ちください。']);
        }

        DB::transaction(function () use ($request, $user, $attendance, $date) {

            // 3. 勤怠修正申請の作成
            $correctRequest = $attendance->correctRequests()->create([
                'user_id'   => $user->id,
                'date'      => $date,
                'check_in'  => $request->check_in,
                'check_out' => $request->check_out,
                'remarks'   => $request->remarks,
                'status'    => 1, // 承認待ち
            ]);

            // 4. 休憩修正申請の作成
            if ($request->has('break_start')) {
                foreach ($request->break_start as $index => $start) {
                    $end = $request->break_end[$index] ?? null;

                    // 開始・終了の両方が入力されている場合のみ保存
                    if (!empty($start) && !empty($end)) {
                        $correctRequest->restCorrectRequests()->create([
                            'break_start' => $start,
                            'break_end'   => $end,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('attendance.detail', ['date' => $date])
            ->with('success', '勤怠データの修正申請をしました');
    }

    /** 申請一覧画面の表示 */
    public function showRequestList(Request $request)
    {
        $user = Auth::user();

        // クエリパラメータから表示モードを取得（デフォルトは承認待ち：pending）
        $statusMode = $request->query('status', 'pending');
        $statusId = ($statusMode === 'approved') ? 2 : 1;

        $requests = $user->attendanceCorrectRequests() // Userモデルに定義したリレーションを使用
            ->where('status', $statusId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('attendance_request_list', [
            'user'     => $user,
            'requests' => $requests,
            'status'   => $statusMode,
        ]);
    }
}
