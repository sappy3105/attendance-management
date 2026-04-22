<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use App\Models\RestCorrectRequest;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 対象となる全スタッフ（role=1）を取得
        $staffUsers = User::where('role', '1')->get();

        // 2. 作成したい期間を設定
        $endDate = Carbon::yesterday(); // 昨日に設定（未来を含めない）
        $startDate = $endDate->copy()->subDays(30); // 30日前から開始

        foreach ($staffUsers as $user) {
            // 各ユーザーごとに1日ずつループ
            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {

                // 土日はデータを作らない
                if ($date->isWeekend()) continue;

                // 3. 勤怠レコードの作成（ユーザーIDと日付のペアで重複チェック）
                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date'    => $date->format('Y-m-d'),
                    ],
                    [
                        'check_in'  => '09:00:00',
                        'check_out' => '18:00:00',
                        'status'    => 3, //退勤済み
                    ]
                );

                // 4. 休憩データの作成
                // 1日1回の休憩（12:00-13:00）を設定
                Rest::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                    ],
                    [
                        'break_start' => '12:00:00',
                        'break_end'   => '13:00:00',
                    ]
                );

                // 5. 10%の確率で、この勤怠に対する修正申請を1件生成し、その50%の確率で承認済みデータを作成する。
                if (fake()->boolean(10)) { // 10%の確率で真になる
                    $status = fake()->boolean(50) ? 2 : 1;

                    $newCheckIn  = '09:30';
                    $newCheckOut = '18:30';

                    // 勤怠修正申請レコードを作成
                    $request = AttendanceCorrectRequest::factory()->create([
                        'user_id'       => $user->id,
                        'attendance_id' => $attendance->id,
                        'date'          => $attendance->date,
                        'check_in'      => $newCheckIn, // 申請内容（例）
                        'check_out'     => $newCheckOut, // 申請内容（例）
                        'status'        => $status,
                    ]);

                    // 休憩申請（元データ）
                    RestCorrectRequest::factory()->create([
                        'attendance_correct_request_id' => $request->id,
                        'break_start' => '12:00',
                        'break_end'   => '13:00',
                    ]);

                    // 休憩申請（追加データ）
                    $newRestRequest = RestCorrectRequest::factory()->create([
                        'attendance_correct_request_id' => $request->id,
                        'break_start' => '14:00',
                        'break_end'   => '15:00',
                    ]);

                    //承認済みの場合、AttendancesテーブルとRestsテーブルを書き換える
                    if ($status === 2) {
                        // 勤怠本体を申請内容で上書き
                        $attendance->update([
                            'check_in'  => $newCheckIn,
                            'check_out' => $newCheckOut,
                            'remarks'   => $request->remarks,
                        ]);

                        // Restsテーブルに追加分だけ作成する
                        $attendance->rests()->create([
                            'break_start' => $newRestRequest->break_start,
                            'break_end'   => $newRestRequest->break_end,
                        ]);
                    }
                }
            }
        }
    }
}
