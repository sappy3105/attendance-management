<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 1]); // 一般ユーザーとして作成
    }

    /**
     * 9-1: 自分が行った勤怠情報が全て表示されている
     */
    public function test_user_can_see_own_attendance_records()
    {
        $date = Carbon::create(2026, 4, 1);
        $this->travelTo($date);

        // リレーションを利用して自身のデータを作成
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

        $response = $this->actingAs($this->user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $date->isoFormat('MM/DD(ddd)'),
            '09:00',
            '18:00',
            '1:00',
            '8:00',
        ]);
    }

    /**
     * 9-2: 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_attendance_list_displays_current_month_correctly()
    {
        $date = Carbon::create(2026, 4, 12);
        $this->travelTo($date);

        $response = $this->actingAs($this->user)->get(route('attendance.list'));

        // bladeの {{ $currentMonth->format('Y/m') }} に合わせた検証
        $response->assertSee('2026/04');
    }

    /**
     * 9-3: 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_button_displays_previous_month_data()
    {
        // 1. 2026年4月に固定
        $currentDate = Carbon::create(2026, 4, 12);
        $this->travelTo($currentDate);

        // 2. 前月（3月15日）のデータを作成
        $prevMonthDate = Carbon::create(2026, 3, 15);
        $attendance = $this->user->attendances()->create([
            'date' => $prevMonthDate,
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 「前月」のパラメータを生成
        $prevMonthParam = $currentDate->copy()->subMonth()->format('Y-m');

        // 4. 前月にアクセス
        $response = $this->actingAs($this->user)->get(route('attendance.list', ['month' => $prevMonthParam]));

        // 4. 検証：3月の表示になっているか、3月のデータがあるか
        $response->assertSee('2026/03');
        $response->assertSeeInOrder([
            $prevMonthDate->isoFormat('MM/DD(ddd)'),
            '09:00',
            '18:00',
            '1:00',
            '8:00',
        ]);
    }

    /**
     * 9-4: 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_next_month_button_displays_next_month_data()
    {
        // 1. 2026年4月に固定
        $currentDate = Carbon::create(2026, 4, 12);
        $this->travelTo($currentDate);

        // 2. 翌月（5月10日）のデータを作成
        $nextMonthDate = Carbon::create(2026, 5, 10);
        $attendance = $this->user->attendances()->create([
            'date' => $nextMonthDate,
            'check_in' => '08:30:00',
            'check_out' => '17:30:00',
            'status' => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:00:00',
            'break_end' => '12:45:00', // 45分休憩
        ]);

        // 翌月のパラメータを計算
        $nextMonthParam = $currentDate->copy()->addMonth()->format('Y-m');

        // 翌月へアクセス
        $response = $this->actingAs($this->user)->get(route('attendance.list', ['month' => $nextMonthParam]));

        // 4. 検証：5月の表示になっているか、5月のデータがあるか
        $response->assertSee('2026/05');
        $response->assertSeeInOrder([
            $nextMonthDate->isoFormat('MM/DD(ddd)'),
            '08:30',
            '17:30',
            '0:45',
            '8:15',
        ]);
    }

    /**
     * 9-5: 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_redirects_to_attendance_detail_page()
    {
        $date = Carbon::create(2026, 4, 12);
        $this->travelTo($date);

        // 1. 勤怠データを作成
        $attendance = $this->user->attendances()->create([
            'date' => $date,
            'check_in' => '09:30:00',
            'check_out' => '18:00:00',
            'status' => 3,
        ]);
        $attendance->rests()->create([
            'break_start' => '12:30:00',
            'break_end' => '13:30:00',
        ]);

        // 2. 勤怠一覧画面にアクセス
        $response = $this->actingAs($this->user)->get(route('attendance.list'));

        // 3. 「詳細」リンク（route('attendance.detail', ['id' => $attendance->id])）が含まれているか確認
        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);

        // 4. 実際にそのURLにアクセスして200が返るか（詳細画面へ遷移できるか）を確認
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('勤怠詳細'); // 詳細画面のタイトル等を確認
        $detailResponse->assertSeeInOrder([
            '日付',
            '2026年',
            '4月12日',
        ]);
        $detailResponse->assertSeeInOrder([
            '出勤・退勤',
            '09:30',
            '18:00',
        ]);
        $detailResponse->assertSeeInOrder([
            '休憩',
            '12:30',
            '13:30',
        ]);
    }
}
