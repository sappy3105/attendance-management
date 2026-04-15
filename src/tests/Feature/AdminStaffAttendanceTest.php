<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AdminStaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成
        $this->admin = User::factory()->admin()->create(['name' => '管理者太郎']);

        // テスト対象のスタッフを作成
        $this->staff = User::factory()->create([
            'name' => 'テストスタッフ1',
            'email' => 'staff1@example.com',
            'role' => 1
        ]);
    }

    /**
     * 14-1: 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_staff_list_displays_all_staff_info()
    {
        // 追加でもう2人一般ユーザーを作成（合計3人）
        User::factory()->create(['name' => 'テストスタッフ2', 'email' => 'staff2@example.com', 'role' => 1]);
        User::factory()->create(['name' => 'テストスタッフ3', 'email' => 'staff3@example.com', 'role' => 1]);

        // スタッフ一覧画面にアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertSee('テストスタッフ1');
        $response->assertSee('staff1@example.com');
        $response->assertSee('テストスタッフ2');
        $response->assertSee('staff2@example.com');
        $response->assertSee('テストスタッフ3');
        $response->assertSee('staff3@example.com');
    }

    /**
     * 14-2: ユーザーの勤怠情報が正しく表示される
     */
    public function test_admin_staff_attendance_list_displays_correct_data()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        // 勤怠データ作成
        $attendance = $this->staff->attendances()->create([
            'date'      => $date->format('Y-m-d'),
            'check_in'  => '09:00:00',
            'check_out' => '18:00:00',
            'status'    => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end'   => '13:00:00',
        ]);

        // スタッフ一覧画面を開き、詳細ボタンリンクが存在しているか確認
        $response = $this->actingAs($this->admin)->get(route('admin.staff.list'));
        $response->assertSee(route('admin.staff.attendance', ['id' => $this->staff->id]));

        // 詳細リンクへ遷移
        $response = $this->actingAs($this->admin)->get(route('admin.staff.attendance', ['id' => $this->staff->id]));

        // 勤怠情報を確認
        $response->assertStatus(200);
        $response->assertSee($this->staff->name . 'の勤怠');
        $response->assertSee('2026/04'); // カレンダー選択部分
        $response->assertSeeInOrder([
            $date->isoFormat('MM/DD(ddd)'),
            '09:00', // 出勤
            '18:00', // 退勤
            '1:00',  // 休憩合計
            '8:00',  // 勤務合計
            '詳細'    // 詳細リンク
        ]);
    }

    /**
     * 14-3: 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_admin_staff_attendance_list_transitions_to_previous_month()
    {
        // 2026年4月15日に固定,前月を設定
        $currentDate = Carbon::create(2026, 4, 15);
        $this->travelTo($currentDate);
        $prevMonth = $currentDate->copy()->subMonth(); // 2026-03
        $prevMonthDate = Carbon::create(2026, 3, 15);

        // 前月（3月15日）のデータを作成
        $attendance = $this->staff->attendances()->create([
            'date' => $prevMonthDate->format('Y-m-d'),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 前月にアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.staff.attendance', [
            'id' => $this->staff->id,
            'month' => $prevMonth->format('Y-m')
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/03'); // カレンダー選択部分
        $response->assertSeeInOrder([
            $prevMonthDate->isoFormat('MM/DD(ddd)'),
            '09:00',
            '18:00',
            '1:00',
            '8:00',
        ]);
    }

    /**
     * 14-4: 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_admin_staff_attendance_list_transitions_to_next_month()
    {
        // 2026年4月15日に固定,翌月を設定
        $currentDate = Carbon::create(2026, 4, 15);
        $this->travelTo($currentDate);

        $nextMonth = $currentDate->copy()->addMonth(); // 2026-05
        $nextMonthDate = Carbon::create(2026, 5, 15);

        // 翌月（5月15日）のデータを作成
        $attendance = $this->staff->attendances()->create([
            'date' => $nextMonthDate->format('Y-m-d'),
            'check_in' => '08:30:00',
            'check_out' => '17:30:00',
            'status' => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end' => '12:45:00',
        ]);

        // 翌月にアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.staff.attendance', [
            'id' => $this->staff->id,
            'month' => $nextMonth->format('Y-m')
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026/05'); // カレンダー選択部分
        $response->assertSeeInOrder([
            $nextMonthDate->isoFormat('MM/DD(ddd)'),
            '08:30',
            '17:30',
            '0:45',
            '8:15',
        ]);
    }

    /**
     * 14-5: 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_admin_staff_attendance_detail_button_transitions_correctly()
    {
        $date = Carbon::create(2026, 4, 12);
        $this->travelTo($date);

        // 勤怠データを作成
        $attendance = $this->staff->attendances()->create([
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:30:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:30:00',
            'break_end' => '13:30:00',
        ]);

        // スタッフ別勤怠一覧画面を開き、詳細ボタンリンクが存在しているか確認
        $response = $this->actingAs($this->admin)->get(route('admin.staff.attendance', ['id' => $this->staff->id]));
        $response->assertSee(route('admin.attendance.detail', ['id' => $attendance->id]));

        // 勤怠詳細画面へアクセス
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        // 勤怠情報を確認
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細'); // 詳細画面のタイトル等を確認
        $response->assertSeeInOrder([
            '日付',
            '2026年',
            '4月12日',
        ]);
        $response->assertSeeInOrder([
            '出勤・退勤',
            '09:30',
            '18:00',
        ]);
        $response->assertSeeInOrder([
            '休憩',
            '12:30',
            '13:30',
        ]);
    }
}
