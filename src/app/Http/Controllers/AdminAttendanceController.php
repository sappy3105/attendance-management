<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use App\Models\RestCorrectRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /** 勤怠一覧画面（管理者） */
    public function list(Request $request)
    {
        // 1. クエリパラメータから日付を取得し、Carbonインスタンス化
        $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
        $currentDate = Carbon::parse($dateStr);

        // 5. 全一般ユーザーを取得し、指定日の勤怠と休憩を一括取得（Eager Load）
        $users = User::where('role', 1)
            ->with(['attendances' => function ($query) use ($dateStr) {
                $query->where('date', $dateStr);
            }])
            ->get();

        return view('admin.attendance_list', [
            'users'       => $users,
            'currentDate' => $currentDate,
            'prevDate'    => $currentDate->copy()->subDay()->format('Y-m-d'), //前日の日付
            'nextDate'    => $currentDate->copy()->addDay()->format('Y-m-d'), //翌日の日付
        ]);
    }

    /** 勤怠詳細画面（管理者） */
    public function showDetail(Request $request, $id)
    {
        // 1. レコードがなければ作成、あれば取得 (firstOrCreate)
        if ($id === 'new') {
            $attendance = Attendance::firstOrCreate(
                [
                    'user_id' => $request->query('user_id'),
                    'date'    => $request->query('date'),
                ],
                ['status' => 3] // 退勤済みとして勤怠の枠を作成
            );
            $attendance->load('user');
        } else {
            // $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
            $attendance = Attendance::with(['user', 'rests', 'correctRequests.restCorrectRequests'])->findOrFail($id);
        }

        // 2. 常にレコードが存在するので、findOrFailなどは不要
        $user = $attendance->user;
        $date = $attendance->date;

        // 3. この勤怠に対して「承認待ち」の修正申請があるか確認
        $pendingRequest = $attendance->correctRequests()
            ->where('status', 1) // 1:承認待ち
            // ->with('restCorrectRequests') // 休憩申請も一緒に取得
            ->first();

        // 承認待ちがあれば、その申請内容を表示データとして使う
        $isPending = !is_null($pendingRequest);

        // 4. 画面に表示する値の取得（申請中なら申請データ、なければ元のデータ）
        $source = $isPending ? $pendingRequest : $attendance;
        $displayData = [
            'check_in'  => $source->check_in,
            'check_out' => $source->check_out,
            'remarks'   => $source->remarks,
        ];

        // 4. 休憩データの取得
        $rests = $isPending ? $pendingRequest->restCorrectRequests : $attendance->rests;

        // 5. ビューの表示
        return view('admin.attendance_detail', [
            'attendance'  => $attendance,
            'user'        => $user,
            'date'        => $date,
            'isPending'   => $isPending,
            'displayData' => $displayData,
            'rests'       => $rests,
        ]);
    }

    /** スタッフ一覧画面（管理者） */
    public function staffList()
    {
        // roleが1（一般スタッフ）のユーザーのみ取得
        $users = User::where('role', 1)
            ->orderBy('id', 'asc')
            ->get();


        return view('admin.staff_list', compact('users'));
    }

    /** スタッフ別勤怠一覧画面  */
    public function staffAttendance(Request $request, $id)
    {
        // 1. ユーザー取得と同時に、対象月の勤怠データを一括取得
        $monthStr = $request->query('month');
        $currentMonth = $monthStr ? Carbon::parse($monthStr)->startOfMonth() : Carbon::today()->startOfMonth();

        $user = User::with(['attendances' => function ($query) use ($currentMonth) {
            $query->with('rests')
                ->whereYear('date', $currentMonth->year)
                ->whereMonth('date', $currentMonth->month);
        }])->findOrFail($id);

        // 2. Bladeで扱いやすいよう、日付をキーにしたコレクションにする
        $attendances = $user->attendances->keyBy(function ($item) {
            return $item->date->format('Y-m-d');
        });

        // 前月・翌月の計算
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // 3. カレンダー作成（1日〜月末まで）
        $calendar = [];
        $daysInMonth = $currentMonth->daysInMonth;
        for ($i = 0; $i < $daysInMonth; $i++) {
            $calendar[] = $currentMonth->copy()->addDays($i);
        }

        return view('admin.staff_attendance_list', [
            'user'         => $user,
            'calendar'     => $calendar,
            'attendances'  => $attendances,
            'currentMonth' => $currentMonth,
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
        ]);
    }

    /** 勤怠詳細の修正 (管理者用) */
    public function updateDetail(AttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::with('rests')->findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {
            // 1. attendancesテーブルの直接更新
            $attendance->update([
                'check_in'  => $request->check_in,
                'check_out' => $request->check_out,
                'remarks'   => $request->remarks,
            ]);

            // 2. restsテーブル（休憩）の更新
            // 管理者の修正時は、一度既存の休憩を削除して作り直すのが最も確実でシンプルな方法です
            $attendance->rests()->delete();

            if ($request->has('break_start')) {
                foreach ($request->break_start as $index => $start) {
                    $end = $request->break_end[$index] ?? null;

                    // 開始・終了の両方が入力されている場合のみ保存
                    if (!empty($start) && !empty($end)) {
                        $attendance->rests()->create([
                            'break_start' => $start,
                            'break_end'   => $end,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.attendance.detail', ['id' => $id])
            ->with('success', '勤怠データを修正しました');
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

        return view('admin.attendance_approve', compact('correctRequest'));
    }

    /** 承認処理の実行 */
    public function approve($attendance_correct_request_id)
    {
        // 1. 申請データを取得（リレーションで勤怠本体と休憩申請も一括取得）
        $correctRequest = AttendanceCorrectRequest::with(['restCorrectRequests'])
            ->findOrFail($attendance_correct_request_id);

        DB::transaction(function () use ($correctRequest) {
            // 2. 本番の勤怠レコード(attendances)を更新
            $attendance = Attendance::findOrFail($correctRequest->attendance_id);
            $attendance->update([
                'check_in'  => $correctRequest->check_in,
                'check_out' => $correctRequest->check_out,
                'remarks'   => $correctRequest->remarks,
            ]);

            // 3. 本番の休憩レコード(rests)を更新
            // 一度削除して、申請された内容で作り直すのが確実です
            $attendance->rests()->delete();
            foreach ($correctRequest->restCorrectRequests as $restRequest) {
                $attendance->rests()->create([
                    'break_start' => $restRequest->break_start,
                    'break_end'   => $restRequest->break_end,
                ]);
            }

            // 4. 申請ステータスを「承認済み(2)」に変更
            $correctRequest->update(['status' => 2]);
        });

        return redirect()->back();
    }


    /** CSVへの書き出し処理 */
    public function export(Request $request)
    {
        // 1. パラメータ（IDと月）を受け取る
        $userId = $request->input('id');
        $monthString = $request->input('month', now()->format('Y-m'));
        $user = User::findOrFail($userId);

        // 1. その月の開始日と終了日を取得
        $startOfMonth = Carbon::parse($monthString)->startOfMonth();
        $endOfMonth = Carbon::parse($monthString)->endOfMonth();

        // 2. 指定期間の勤怠データを取得し、日付をキーにしたコレクションにする
        $attendances = Attendance::with('user', 'rests')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $csvHeader = ['スタッフ名', '日付', '出勤', '退勤', '休憩時間合計', '労働時間合計', '備考'];
        $csvData = [];
        $csvData[] = $csvHeader;

        // 3. 1日から末日まで1日ずつループを回す
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            // その日の勤怠データがあるか確認
            $attendance = $attendances->get($dateStr);

            // 日付フォーマットを定義
            $formattedDate = $date->format('Y/m/d');

            if ($attendance) {
                $csvData[] = [
                    $user->name,
                    $formattedDate,
                    $attendance->check_in ? $attendance->check_in->format('H:i') : '',
                    $attendance->check_out ? $attendance->check_out->format('H:i') : '',
                    $attendance->getTotalRestTime(), //休憩時間合計
                    $attendance->getTotalWorkTime(), //労働時間合計
                    $attendance->remarks, //備考
                ];
            } else {
                // データがない場合（日付と名前以外は空欄）
                $csvData[] = [
                    $user->name,
                    $formattedDate,
                    '',
                    '',
                    '',
                    '',
                    ''
                ];
            }
        }

        $filename = 'attendance_' . $user->name . '_' . str_replace('-', '', $monthString) . '.csv';

        // 3. ファイル書き出し処理
        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // 文字化け対策(BOM)

            // 2. データを1行ずつ取り出して書き込む
            foreach ($csvData as $row) {
                // $row が配列であることを確認して書き込む
                fputcsv($file, $row);
            }
            fclose($file);
        };

        // 4. レスポンスヘッダーの設定
        $headers = [
            "Content-type" => "text/csv", //CSVデータという宣言
            "Content-Disposition" => "attachment; filename=$filename", //画面に表示せず、「添付ファイル（attachment）」として扱い、この「ファイル名（filename）」で保存してください、という指示
        ];
        return response()->stream($callback, 200, $headers);
    }
}
