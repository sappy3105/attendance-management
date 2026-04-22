<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceCorrectRequest;
use Carbon\Carbon;

class UserAttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 1]); // 一般ユーザーとして作成
    }

    /**
     * 11-1: 出勤時間が退勤時間より後にある場合、エラーメッセージが表示される
     */
    public function test_user_attendance_update_validation_error_check_in_after_check_out()
    {
        $date = Carbon::create(2026, 4, 12);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '19:00', // 退勤より後
            'check_out' => '18:00',
            'remarks' => 'バリデーションチェック',
        ]);

        $response->assertSessionHasErrors(['check_out']);

        // 詳細画面を再取得
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // エラーメッセージが画面に表示されているか
        $response->assertSeeInOrder([
            '出勤・退勤',
            '19:00',
            '18:00',
            '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 11-2: 休憩開始時間が退勤時間より後にある場合、エラーメッセージが表示される
     */
    public function test_user_attendance_update_validation_error_break_start_after_check_out()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00',
            'break_start' => ['19:00'], // 退勤より後
            'break_end' => ['20:00'],
            'remarks' => '休憩バリデーションチェック',
        ]);

        $response->assertSessionHasErrors(['break_start.0']);


        // 詳細画面を再取得
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // エラーメッセージが画面に表示されているか
        $response->assertSeeInOrder([
            '休憩',
            '19:00',
            '20:00',
            '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 11-3: 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_user_attendance_update_validation_error_break_end_after_check_out()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00',
            'break_start' => ['12:00'],
            'break_end' => ['19:00'], // 退勤より後
            'remarks' => '休憩バリデーションチェック',
        ]);

        $response->assertSessionHasErrors(['break_end.0']);


        // 詳細画面を再取得
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // エラーメッセージが画面に表示されているか
        $response->assertSeeInOrder([
            '休憩',
            '12:00',
            '19:00',
            '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 11-4: 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_user_attendance_update_validation_error_remarks_required()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00',
            'remarks' => '', // 未入力
        ]);

        $response->assertSessionHasErrors(['remarks']);

        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSeeInOrder([
            '備考',
            '備考を記入してください',
        ]);
    }

    /**
     * 11-5: 修正申請処理が実行される
     */
    public function test_user_attendance_update_creates_request_correctly()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '10:00',
            'check_out' => '19:00',
            'break_start' => ['12:00'],
            'break_end' => ['13:00'],
            'remarks' => '修正申請のテストです',
        ]);

        // 詳細画面にリダイレクトされること
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHas('success', '勤怠データの修正申請をしました');

        // データベースに修正申請(AttendanceCorrectRequest)が作成されているか確認
        $this->assertDatabaseHas('attendance_correct_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->user->id,
            'check_in' => '10:00',
            'check_out' => '19:00',
            'remarks' => '修正申請のテストです',
            'status' => 1, // 承認待ち
        ]);

        // 管理者ユーザーを作成（role=2）
        $admin = User::factory()->admin()->create(['role' => 2]);

        // 1. 管理者として「申請一覧画面」を確認
        $response = $this->actingAs($admin)->get(route('attendance.requests'));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '承認待ち',
            $this->user->name,
            $date->format('Y/m/d'),
            '修正申請のテストです',
            '詳細', // 詳細リンクがあること
        ]);

        // データベースから作成された申請IDを取得
        $correctRequest = AttendanceCorrectRequest::where('attendance_id', $attendance->id)->first();

        // 2. 管理者として「修正申請承認画面」を確認
        $response = $this->actingAs($admin)->get(route('admin.attendance.approve.show', [
            'attendance_correct_request_id' => $correctRequest->id
        ]));
        $response->assertStatus(200);

        // 承認画面に修正後の値が正しく並んでいるか検証（assertSeeInOrder）
        $response->assertSeeInOrder([
            '名前',
            $this->user->name,
            '日付',
            '2026年',
            '4月15日',
            '出勤・退勤',
            '10:00',
            '19:00',
            '休憩',
            '12:00',
            '13:00',
            '備考',
            '修正申請のテストです',
            '承認' // 承認ボタンが表示されていること
        ]);
    }

    /**
     * 11-6: 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_user_can_view_own_request_list()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        // 申請データを作成しておく
        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00',
            'check_out' => '18:00',
            'status' => 3,
        ]);

        // 修正申請をする
        $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '10:00',
            'check_out' => '19:00',
            'break_start' => ['12:00'],
            'break_end' => ['13:00'],
            'remarks' => '自己申請の確認テスト',
        ]);

        // 申請一覧画面へアクセス（ユーザー側）
        $response = $this->actingAs($this->user)->get(route('attendance.requests', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '承認待ち',
            $this->user->name,
            $date->format('Y/m/d'),
            '自己申請の確認テスト',
            '詳細'
        ]);
    }

    /**
     * 11-7: 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_admin_can_approve_request_and_update_attendance()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00',
            'check_out' => '18:00',
            'status' => 3,
        ]);

        // 修正申請をする
        $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '10:00',
            'check_out' => '19:00',
            'break_start' => ['12:00'],
            'break_end' => ['13:00'],
            'remarks' => '管理者承認テスト',
        ]);

        // 承認するために、DBから申請データを探してIDを取得
        $correctRequest = AttendanceCorrectRequest::where('remarks', '管理者承認テスト')->first();

        // 管理者を作成
        $admin = User::factory()->admin()->create();

        // 管理者が承認処理を実行
        $this->actingAs($admin)->post(route('admin.attendance.approve', [
            'attendance_correct_request_id' => $correctRequest->id
        ]));

        // 一般ユーザーでログインし直し、「承認済み」タブを確認
        $response = $this->actingAs($this->user)->get(route('attendance.requests', ['status' => 'approved']));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '承認済み',
            $this->user->name,
            '2026/04/15',
            '管理者承認テスト',
            '詳細'
        ]);
    }

    /**
     * 11-8: 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_user_can_transition_from_request_list_to_detail()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00',
            'check_out' => '18:00',
            'status' => 3,
        ]);

        // 修正ボタン押下（申請作成）
        $this->actingAs($this->user)->post(route('attendance.update', ['id' => $attendance->id]), [
            'date' => $date->format('Y-m-d'),
            'check_in' => '11:00',
            'check_out' => '20:00',
            'break_start' => ['14:00'],
            'break_end' => ['15:00'],
            'remarks' => '画面遷移テスト',
        ]);

        // 申請一覧画面を開く
        $response = $this->actingAs($this->user)->get(route('attendance.requests', ['status' => 'pending']));

        // 申請データをDBから取得
        $correctRequest = AttendanceCorrectRequest::where('remarks', '画面遷移テスト')->first();

        // 一覧にある「詳細」リンクのURLを確認
        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);

        // 詳細リンクへアクセスして、勤怠詳細に移動するか確認
        $transitionResponse = $this->get($detailUrl);
        $transitionResponse->assertStatus(200);
        $transitionResponse->assertSee('勤怠詳細'); // 遷移先の見出しを確認
    }
}
