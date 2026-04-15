<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceCorrectRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成
        $this->admin = User::factory()->admin()->create(['name' => '管理者太郎']);

        // 一般ユーザーを3人作成
        $this->users = User::factory()->count(3)->create(['role' => 1]);
    }

    /**
     * 15-1: 承認待ちの修正申請が全て表示されている
     */
    public function test_admin_can_see_all_pending_requests()
    {
        // 3人のユーザーそれぞれに承認待ちの修正申請(status = 1)を作成
        foreach ($this->users as $index => $user) {
            $date = Carbon::create(2026, 4, 10 + $index);

            // 勤怠データのベースを作成
            $attendance = $user->attendances()->create([
                'date'      => $date->format('Y-m-d'),
                'check_in'  => '09:00:00',
                'check_out' => '18:00:00',
                'status'    => 3,
            ]);

            // 修正申請（承認待ち）を作成
            $user->attendanceCorrectRequests()->create([
                'attendance_id' => $attendance->id,
                'date'          => $date->format('Y-m-d'),
                'check_in'      => '08:30:00',
                'check_out'     => '17:30:00',
                'remarks'       => "承認待ちの備考{$index}",
                'status'        => 1, // 承認待ち
            ]);
        }

        // 申請一覧画面へアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.requests', ['status' => 'pending']));
        $response->assertStatus(200);

        // 各ユーザーの状態、名前、対象日時、申請理由が画面に表示されているか確認
        foreach ($this->users as $index => $user) {
            $date = Carbon::create(2026, 4, 10 + $index);
            $response->assertSeeInOrder([
                '承認待ち',
                $user->name,
                $date->format('Y/m/d'),
                "承認待ちの備考{$index}"
            ]);
        }
    }

    /**
     * 15-2: 承認済みの修正申請が全て表示されている
     */
    public function test_admin_can_see_all_approved_requests()
    {
        // 3人のユーザーそれぞれに承認済みの修正申請(status = 2)を作成
        foreach ($this->users as $index => $user) {
            $date = Carbon::create(2026, 4, 20 + $index);

            $attendance = $user->attendances()->create([
                'date'      => $date->format('Y-m-d'),
                'check_in'  => '09:00:00',
                'check_out' => '18:00:00',
                'status'    => 3,
            ]);

            $user->attendanceCorrectRequests()->create([
                'attendance_id' => $attendance->id,
                'date'          => $date->format('Y-m-d'),
                'check_in'      => '09:30:00',
                'check_out'     => '18:30:00',
                'remarks'       => "承認済みの備考{$index}",
                'status'        => 2, // 承認済み
            ]);
        }

        // 承認済み画面へアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.requests', ['status' => 'approved']));
        $response->assertStatus(200);

        // 各ユーザーの状態、名前、対象日時、申請理由が画面に表示されているか確認
        foreach ($this->users as $index => $user) {
            $date = Carbon::create(2026, 4, 20 + $index);
            $response->assertSeeInOrder([
                '承認済み',
                $user->name,
                $date->format('Y/m/d'),
                "承認済みの備考{$index}"
            ]);
        }
    }

    /**
     * 15-3: 修正申請の詳細内容が正しく表示されている
     */
    public function test_admin_attendance_correct_request_detail_displays_correctly()
    {
        $user = $this->users[0];
        $date = Carbon::create(2026, 4, 15);

        $attendance = $user->attendances()->create([
            'date'      => $date->format('Y-m-d'),
            'check_in'  => '09:00:00',
            'check_out' => '18:00:00',
            'status'    => 3,
        ]);

        $correctRequest = $user->attendanceCorrectRequests()->create([
            'attendance_id' => $attendance->id,
            'date'          => $date->format('Y-m-d'),
            'check_in'      => '08:30:00',
            'check_out'     => '17:30:00',
            'remarks'       => "詳細確認用のテスト備考",
            'status'        => 1, // 承認待ち
        ]);

        // 休憩データもリレーションを利用して作成
        $correctRequest->restCorrectRequests()->create([
            'break_start' => '12:00:00',
            'break_end'   => '13:00:00',
        ]);

        // 承認画面へアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.approve.show', ['attendance_correct_request_id' => $correctRequest->id]));

        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '名前',
            $user->name,
        ]);
        $response->assertSeeInOrder([
            '日付',
            '2026年',
            '4月15日', // Bladeの実装(M月D日)に合わせた表記
        ]);
        $response->assertSeeInOrder([
            '出勤・退勤',
            '08:30',
            '17:30',
        ]);
        $response->assertSeeInOrder([
            '休憩',
            '12:00',
            '13:00',
        ]);
        $response->assertSeeInOrder([
            '備考',
            '詳細確認用のテスト備考',
        ]);

        // 「承認」ボタンとURLが表示されることを確認
        $response->assertSee('承認');
        $response->assertSee(route('admin.attendance.approve', ['attendance_correct_request_id' => $correctRequest->id]));
    }

    /**
     * 15-4: 修正申請の承認処理が正しく行われる
     */
    public function test_admin_can_approve_attendance_correct_request()
    {
        $user = $this->users[0];
        $date = Carbon::create(2026, 4, 16);
        $this->travelTo($date);
        $dateStr = $date->format('Y-m-d');

        $attendance = $user->attendances()->create([
            'date'      => $dateStr,
            'check_in'  => '09:00:00',
            'check_out' => '18:00:00',
            'status'    => 3,
        ]);

        // 修正申請データを作成
        $correctRequest = $user->attendanceCorrectRequests()->create([
            'attendance_id' => $attendance->id,
            'date'          => $dateStr,
            'check_in'      => '08:30:00',
            'check_out'     => '17:30:00',
            'remarks'       => "承認処理テスト用",
            'status'        => 1, // 承認前
        ]);

        // 休憩の修正申請データも作成
        $correctRequest->restCorrectRequests()->create([
            'break_start' => '12:15:00',
            'break_end' => '13:15:00',
        ]);

        // 承認実行
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.approve', ['attendance_correct_request_id' => $correctRequest->id]));

        // DBの修正申請データが「承認済み(2)」に更新されているか確認
        $this->assertDatabaseHas('attendance_correct_requests', [
            'id'     => $correctRequest->id,
            'status' => 2,
        ]);

        // 大元の勤怠データ(attendances)が書き換わったか確認
        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'check_in'  => $dateStr . ' 08:30:00',
            'check_out' => $dateStr . ' 17:30:00',
            'remarks'   => '承認処理テスト用',
        ]);

        // 休憩データ(rests)が書き換わったか確認
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_start'   => $dateStr . ' 12:15:00',
            'break_end'     => $dateStr . ' 13:15:00',
        ]);
    }
}
