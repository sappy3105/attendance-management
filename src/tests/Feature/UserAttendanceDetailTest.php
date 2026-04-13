<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 1]); // 一般ユーザーとして作成
    }


    /**
     * 10-1: 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_user_attendance_detail_displays_correct_user_name()
    {
        $date = Carbon::create(2026, 4, 12); // 固定
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'status' => 1,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '名前',
            $this->user->name,
        ]);
    }


    /**
     * 10-2: 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_user_attendance_detail_displays_correct_date()
    {
        $date = Carbon::create(2026, 4, 12);
        $this->travelTo($date);

        // リレーションを使用してデータ作成
        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
        // ラベルと値が順番に並んでいるか検証
        $response->assertSeeInOrder([
            '日付',
            '2026年',
            '4月12日',
        ]);
    }

    /**
     * 10-3: 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_user_attendance_detail_displays_correct_attendance_data()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '08:30:00',
            'check_out' => '17:30:00',
            'status' => 3,
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.detail', ['id' => $attendance->id]));

        // 各項目が正しい順序で表示されているか検証
        $response->assertSeeInOrder([
            '出勤・退勤',
            '08:30',
            '17:30',
        ]);
    }

    /**
     * 10-4: 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_user_attendance_detail_displays_correct_rest_data()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '08:30:00',
            'check_out' => '17:30:00',
            'status' => 3,
        ]);

        // 休憩データを2件作成（リレーション利用）
        $attendance->rests()->create(['break_start' => '12:00:00', 'break_end' => '12:30:00']);
        $attendance->rests()->create(['break_start' => '15:00:00', 'break_end' => '15:15:00']);

        $response = $this->actingAs($this->user)->get(route('attendance.detail', ['id' => $attendance->id]));

        // 各項目が正しい順序で表示されているか検証
        $response->assertSeeInOrder([
            '休憩',
            '12:00',
            '12:30',
            '休憩2',
            '15:00',
            '15:15',
        ]);
    }
}
