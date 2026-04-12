<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AttendanceCheckOutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // テストユーザー作成
        $this->user = User::factory()->create();
    }

    /**
     * 8-1: 退勤ボタンが正しく機能する
     */
    public function test_check_out_button_functions_correctly()
    {
        // 基準となる日付を固定
        $date = Carbon::create(2026, 4, 12, 9, 0, 0);
        $this->travelTo($date);

        // 出勤状態のデータを作成
        $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'status' => 1, // 出勤中
        ]);

        // 2. 勤怠画面にアクセス
        $this->actingAs($this->user)->get(route('attendance.index'))
            ->assertSee('退勤');

        // 退勤処理を実行
        $this->travelTo($date->copy()->setTimeFromTimeString('18:00:00'));
        $this->post('/attendance/check-out');

        // 4. ステータスが「退勤済」になり、感謝のメッセージが表示されるか
        $response = $this->get(route('attendance.index'));
        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');

        // 5. DBの退勤時刻が更新されているか
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'check_out' => '18:00:00',
            'status' => 3,
        ]);
    }

    /**
     * 8-2: 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_check_out_time_is_visible_on_attendance_list()
    {
        // 基準となる日付を固定
        $date = Carbon::create(2026, 4, 12, 9, 0, 0);
        $this->travelTo($date);

        // ログインして勤怠画面を開く
        $this->actingAs($this->user)->get(route('attendance.index'));

        // 出勤・退勤処理（POSTリクエスト）を実行
        $this->actingAs($this->user)->post('/attendance/check-in');
        $this->travelTo($date->copy()->setTimeFromTimeString('18:00:00'));
        $this->actingAs($this->user)->post('/attendance/check-out');

        //  勤怠一覧画面へ移動
        $response = $this->actingAs($this->user)->get(route('attendance.list'));

        // 日付、出勤、退勤が正しい順序で表示されているか
        $formattedDate = $date->isoFormat('MM/DD(ddd)');
        $response->assertSeeHtmlInOrder([
            '<td>' . $formattedDate . '</td>',
            '<td>09:00</td>',
            '<td>18:00</td>'
        ]);
    }
}
