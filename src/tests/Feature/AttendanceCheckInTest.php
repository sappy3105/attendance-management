<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp(); // テストユーザーを作成
        $this->user = User::factory()->create();
    }

    /**
     * 6-1: 出勤ボタンが正しく機能する
     */
    public function test_check_in_button_functions_correctly()
    {
        $date = Carbon::create(2026, 4, 12, 9, 0, 0);
        $this->travelTo($date);

        // ログインして勤怠画面を開く
        $response = $this->actingAs($this->user)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤'); // ボタンが表示されていることを確認

        // 出勤処理（POSTリクエスト）を実行
        $response = $this->actingAs($this->user)->post('/attendance/check-in');

        // 処理後のリダイレクトとステータス変化を確認
        $response->assertRedirect();
        $this->get(route('attendance.index'))->assertSee('出勤中');

        // データベースに正しく保存されているか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'status' => 1,
            'check_in' => '09:00:00',
        ]);
    }

    /**
     * 6-2: 出勤は一日一回のみできる
     */
    public function test_cannot_check_in_twice_a_day()
    {
        $date = Carbon::create(2026, 4, 12, 18, 0, 0);
        $this->travelTo($date);

        // すでに退勤済みのデータを作成しておく
        $this->user->attendances()->create([
            'date' => $date->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00:00',
            'status' => 3, // 退勤済
        ]);

        // 勤怠画面を開く
        $response = $this->actingAs($this->user)->get(route('attendance.index'));

        // 出勤ボタンが表示されていないことを確認
        $response->assertStatus(200);
        $response->assertDontSee('出勤');
        $response->assertSee('お疲れ様でした。');
    }

    /**
     * 6-3: 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_check_in_time_is_visible_on_list_page()
    {
        // 9:05に出勤した想定
        $date = Carbon::create(2026, 4, 12, 9, 5, 0);
        $this->travelTo($date);

        // 出勤処理を実行
        $this->actingAs($this->user)->post('/attendance/check-in');

        // 勤怠一覧画面に移動
        $response = $this->actingAs($this->user)->get(route('attendance.list'));
        $response->assertStatus(200);

        // 日付の行の中に、出勤時刻が含まれているか
        // isoFormat('MM/DD(ddd)') に基づき「04/12(日)」を特定
        $formattedDate = $date->isoFormat('MM/DD(ddd)');

        $response->assertSeeInOrder([
            $formattedDate,
            '09:05'
        ]);

        // さらにHTML構造を特定して検証
        $response->assertSeeHtmlInOrder([
            '<td>' . $formattedDate . '</td>',
            '<td>09:05</td>'
        ]);
    }
}
