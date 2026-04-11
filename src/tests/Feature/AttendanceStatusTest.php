<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
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
     * 5-1: 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_outside_working_hours()
    {
        // 今日の勤怠レコードがない状態 ＝ 勤務外
        $response = $this->actingAs($this->user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * 5-2: 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_working()
    {
        // テスト上の「今」を固定
        $this->travelTo(Carbon::create(2026, 4, 11, 10, 0, 0));

        // 今日の勤怠レコードを作成（status 1: 出勤中）
        $this->user->attendances()->create([
            'date' => now(),
            'check_in' => '09:00',
            'status' => 1,
        ]);
        // Attendance::create([
        //     'user_id' => $this->user->id,
        //     'date' => $today,
        //     'check_in' => '09:00:00',
        //     'status' => 1,
        // ]);

        $response = $this->actingAs($this->user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 5-3: 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_on_break()
    {
        // テスト上の「今」を固定
        $this->travelTo(Carbon::today());

        // 今日の勤怠レコードを作成（status 2: 休憩中）
        $this->user->attendances()->create([
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in' => '09:00:00',
            'status' => 2,
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 5-4: 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_worked_out()
    {
        // テスト上の「今」を固定
        $this->travelTo(Carbon::today());

        // 今日の勤怠レコードを作成（status 3: 退勤済）
        $this->user->attendances()->create([
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
