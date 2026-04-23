<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成
        $this->admin = User::factory()->admin()->create(['name' => '管理者太郎']);

        // 一般ユーザー（スタッフ）を作成
        $this->user = User::factory()->create(['name' => 'テストスタッフ', 'role' => 1]);
    }

    /**
     * 13-1: 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_admin_attendance_detail_displays_correct_data()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        // テスト用勤怠データ作成
        $attendance = $this->user->attendances()->create([
            'date'      => $date->format('Y-m-d'),
            'check_in'  => '09:00:00',
            'check_out' => '18:00:00',
            'remarks'   => 'これはテストデータです',
            'status'    => 3, // 退勤済み
        ]);

        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end'   => '13:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '名前',
            $this->user->name,
            '日付',
            '2026年',
            '4月15日',
            '出勤・退勤',
            '09:00',
            '18:00',
            '休憩',
            '12:00',
            '13:00',
            '備考',
            'これはテストデータです'
        ]);
    }

    /**
     * 13-2: 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_update_validation_error_check_in_after_check_out()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in'  => '09:00:00',
            'check_out' => '18:00:00',
            'status'    => 3,
        ]);

        // 出勤時間が退勤時間より後の場合
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', $attendance->id), [
            'date' => $date->format('Y-m-d'),
            'check_in'  => '19:00', // 退勤より後
            'check_out' => '18:00',
            'remarks'   => '出勤・退勤バリデーションチェック',
        ]);

        $response->assertSessionHasErrors(['check_out']);

        // 詳細画面を再取得
        $response = $this->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        // エラーメッセージが画面に表示されているか
        $response->assertSeeInOrder([
            '出勤・退勤',
            '19:00',
            '18:00',
            '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 13-3: 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_update_validation_error_break_start_after_check_out()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in'  => '09:00:00',
            'check_out' => '18:00:00',
            'status'    => 3,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00',
            'break_start' => ['19:00'], // 退勤より後
            'break_end' => ['20:00'],
            'remarks' => '休憩バリデーションチェック',
        ]);

        $response->assertSessionHasErrors(['break_start.0']);


        // 詳細画面を再取得
        $response = $this->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        // エラーメッセージが画面に表示されているか
        $response->assertSeeInOrder([
            '休憩',
            '19:00',
            '20:00',
            '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 13-4: 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_update_validation_error_break_end_after_check_out()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00',
            'break_start' => ['12:00'],
            'break_end' => ['19:00'], // 退勤より後
            'remarks' => '休憩バリデーションチェック',
        ]);

        $response->assertSessionHasErrors(['break_end.0']);

        // 詳細画面を再取得
        $response = $this->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        // エラーメッセージが画面に表示されているか
        $response->assertSeeInOrder([
            '休憩',
            '12:00',
            '19:00',
            '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 13-5: 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_admin_attendance_update_validation_error_remarks_required()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00',
            'remarks' => '', // 未入力
        ]);

        $response->assertSessionHasErrors(['remarks']);

        $response = $this->get(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSeeInOrder([
            '備考',
            '備考を記入してください',
        ]);
    }
}
