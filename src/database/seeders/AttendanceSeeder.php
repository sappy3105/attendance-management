<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 対象となる全スタッフ（role=1）を取得
        // メールアドレスで固定したい場合は whereIn(['staff1@example.com', ...]) でもOK
        $staffUsers = User::where('role', '1')->get();

        // 2. 作成したい期間（今月の1日〜末日）を設定
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        foreach ($staffUsers as $user) {
            // 各ユーザーごとに1日ずつループ
            for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {

                // 土日はデータを作らない場合は以下を有効化（任意）
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
                        'status'    => 4, //退勤済み
                    ]
                );

                // 4. 休憩データの作成
                // 1日1回の休憩（12:00-13:00）を想定
                Rest::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                    ],
                    [
                        'break_start' => '12:00:00',
                        'break_end'   => '13:00:00',
                    ]
                );
            }
        }
    }
}
