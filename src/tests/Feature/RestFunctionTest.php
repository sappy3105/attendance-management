<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class RestFunctionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // テストユーザーを作成
        $this->user = User::factory()->create();
    }

    /**
     * 7-1: 休憩ボタンが正しく機能する
     */
    public function test_break_start_button_functions_correctly()
    {
        // 基準となる日付を固定
        $date = Carbon::create(2026, 4, 12, 9, 0, 0);
        $this->travelTo($date);

        // 出勤状態のデータを作成（リレーションを利用）
        $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'status' => 1, // 出勤中
        ]);

        // 勤怠画面を開く
        $response = $this->actingAs($this->user)->get(route('attendance.index'));
        $response->assertSee('休憩入');

        // 休憩入処理を実行
        $this->travelTo($date->copy()->setTimeFromTimeString('12:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-start');

        // ステータスが「休憩中」に更新されているか確認
        $this->get(route('attendance.index'))->assertSee('休憩中');
        $this->assertDatabaseHas('rests', [
            'break_start' => '12:00:00',
        ]);
    }

    /**
     * 7-2: 休憩は一日に何回でもできる
     */
    public function test_can_take_multiple_breaks_in_a_day()
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

        // 1回目の休憩（12:00〜13:00）処理を行う
        $this->travelTo($date->copy()->setTimeFromTimeString('12:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-start');
        $this->travelTo($date->copy()->setTimeFromTimeString('13:00:00'));
        $this->post('/attendance/break-end');

        // 勤怠画面を開き、再度「休憩入」ボタンが表示されているか確認
        $response = $this->actingAs($this->user)->get(route('attendance.index'));
        $response->assertSee('休憩入');
    }

    /**
     * 7-3: 休憩戻ボタンが正しく機能する
     */
    public function test_break_end_button_functions_correctly()
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

        // 休憩入の処理を行う
        $this->travelTo($date->copy()->setTimeFromTimeString('12:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-start');

        // 休憩中画面で「休憩戻」が出るか確認
        $this->get(route('attendance.index'))->assertSee('休憩戻');

        // 休憩戻の処理を行う
        $this->travelTo($date->copy()->setTimeFromTimeString('13:00:00'));
        $this->post('/attendance/break-end');

        // ステータスが「出勤中」に戻っているか確認
        $this->get(route('attendance.index'))->assertSee('出勤中');
        $this->assertDatabaseHas('rests', [
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);
    }

    /**
     * 7-4: 休憩戻は一日に何回でもできる
     */
    public function test_can_end_multiple_breaks_in_a_day()
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

        // 1回目の休憩（12:00〜13:00）処理を行う
        $this->travelTo($date->copy()->setTimeFromTimeString('12:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-start');
        $this->travelTo($date->copy()->setTimeFromTimeString('13:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-end');

        // 2回目の休憩（開始のみ）処理を行う
        $this->travelTo($date->copy()->setTimeFromTimeString('15:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-start');

        // 勤怠画面を開き、「休憩戻」ボタンが表示されているか確認
        $response = $this->actingAs($this->user)->get(route('attendance.index'));
        $response->assertSee('休憩戻');
    }

    /**
     * 7-5: 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_rest_time_is_visible_on_list_page()
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

        // 1回目の休憩（12:00〜13:00）処理を行う
        $this->travelTo($date->copy()->setTimeFromTimeString('12:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-start');
        $this->travelTo($date->copy()->setTimeFromTimeString('13:00:00'));
        $this->actingAs($this->user)->post('/attendance/break-end');

        // 勤怠一覧画面に移動
        $response = $this->actingAs($this->user)->get(route('attendance.list'));
        $response->assertStatus(200);

        // 日付 -> 出勤 -> 退勤 -> 休憩合計 の順
        $formattedDate = $date->isoFormat('MM/DD(ddd)');

        $response->assertSeeHtmlInOrder([
            '<td>' . $formattedDate . '</td>',
            '<td>09:00</td>',
            '<td>',
            '</td>',
            '<td>1:00</td>' // getTotalRestTime() の返り値が 01:00 であると想定
        ]);
    }
}
