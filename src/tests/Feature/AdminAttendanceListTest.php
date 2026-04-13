<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    // protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成
        $this->admin = User::factory()->admin()->create([
            'name' => '管理者太郎'
        ]);

        // 一般ユーザー3名を作成
        // $this->users = User::factory()->count(3)->create(['role' => 1]);
    }

    /**
     * 12-1: その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_admin_attendance_list_shows_all_staff_attendance()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);
        // Carbon::setTestNow($date);

        // 3人のユーザーにそれぞれ異なる勤務時間を作成
        $testData = [
            [
                'name'           => 'ユーザー1',
                'in'             => '09:00:00', // DB流し込み用
                'out'            => '18:00:00',
                'check_in_disp'  => '09:00', // 表示チェック用
                'check_out_disp' => '18:00',
                'b_start'        => '12:00:00',
                'b_end'          => '13:00:00',
                'rest'           => '1:00',
                'work'           => '8:00'
            ],
            [
                'name'           => 'ユーザー2',
                'in'             => '10:00:00',
                'out'            => '20:00:00',
                'check_in_disp'  => '10:00',
                'check_out_disp' => '20:00',
                'b_start'        => '13:00:00',
                'b_end'          => '14:30:00',
                'rest'           => '1:30',
                'work'           => '8:30'
            ],
            [
                'name'           => 'ユーザー3',
                'in'             => '08:00:00',
                'out'            => '12:00:00',
                'check_in_disp'  => '08:00',
                'check_out_disp' => '12:00',
                'b_start'        => null,
                'b_end'          => null,
                'rest'           => '0:00',
                'work'           => '4:00'
            ],
        ];

        // 各ユーザーの勤怠データをループで作成
        foreach ($testData as $data) {
            $user = User::factory()->create(['name' => $data['name'], 'role' => 1]);
            $attendance = $user->attendances()->create([
                'date'      => $date->format('Y-m-d'),
                'check_in'  => $data['in'],
                'check_out' => $data['out'],
                'status'    => 3,
            ]);
            if ($data['b_start']) {
                $attendance->rests()->create([
                    'break_start' => $data['b_start'],
                    'break_end'   => $data['b_end'],
                ]);
            }
        }

        // 管理者でログインして、勤怠一覧画面を表示
        // $response = $this->actingAs($this->admin)->get(route('admin.attendance.list', ['date' => $date->format('Y-m-d')]));
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-04-15');
        $response->assertStatus(200);

        // ループで各ユーザーの情報がテーブルの並び順通りに表示されているか検証
        foreach ($testData as $data) {
            $response->assertSeeInOrder([
                $data['name'],
                $data['check_in_disp'],  // 09:00
                $data['check_out_disp'], // 18:00
                $data['rest'],           // 1:00
                $data['work'],           // 8:00
            ]);
        }
        // foreach ($testData as $data) {
        //     $response->assertSee($data['name']);
        //     $response->assertSee($data['in']);
        //     $response->assertSee($data['out']);
        //     $response->assertSee($data['rest']);
        //     $response->assertSee($data['work']);
        // }
    }

    /**
     * 12-2: 遷移した際に現在の日付が表示される
     */
    public function test_admin_attendance_list_displays_current_date()
    {
        $date = Carbon::create(2026, 4, 15);
        $this->travelTo($date);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list'));

        $response->assertStatus(200);
        // Bladeの表示形式に合わせて検証（例：2026年04月20日の勤怠）
        $response->assertSee('2026年04月15日の勤怠');
        // カレンダーの日付表示も確認
        $response->assertSee('2026/04/15');
    }
    /**
     * 12-3: 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_admin_attendance_list_transition_to_previous_day_with_data()
    {
        $today = Carbon::create(2026, 4, 15);
        $yesterday = $today->copy()->subDay();
        $this->travelTo($today);

        // 前日のテストデータ（3名分）
        $testData = [
            [
                'name'           => 'ユーザー1',
                'in'             => '09:00:00', // DB流し込み用
                'out'            => '18:00:00',
                'check_in_disp'  => '09:00', // 表示チェック用
                'check_out_disp' => '18:00',
                'rest'           => '1:00',
                'work'           => '8:00'
            ],
            [
                'name'           => 'ユーザー2',
                'in'             => '10:00:00',
                'out'            => '19:00:00',
                'check_in_disp'  => '10:00',
                'check_out_disp' => '19:00',
                'rest'           => '1:00',
                'work'           => '8:00'

            ],
            [
                'name'           => 'ユーザー3',
                'in'             => '08:00:00',
                'out'            => '17:00:00',
                'check_in_disp'  => '08:00',
                'check_out_disp' => '17:00',
                'rest'           => '1:00',
                'work'           => '8:00'
            ],
        ];

        // 前日のデータを作成
        foreach ($testData as $data) {
            $user = User::factory()->create(['name' => $data['name'], 'role' => 1]);
            $attendance = $user->attendances()->create([
                'date' => $yesterday,
                'check_in' => $data['in'],
                'check_out' => $data['out'],
                'status' => 3,
            ]);
            $attendance->rests()->create([
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        // 前日の日付パラメータ付きでアクセス（「前日」ボタンの挙動）
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list', ['date' => $yesterday->format('Y-m-d')]));

        $response->assertStatus(200);
        $response->assertSee($yesterday->format('Y年m月d日') . 'の勤怠');

        // 各ユーザーのデータが正確に表示されているかループで検証
        foreach ($testData as $data) {
            $response->assertSeeInOrder([
                $data['name'],
                $data['check_in_disp'],
                $data['check_out_disp'],
                $data['rest'],
                $data['work'],
            ]);
        }
    }

    /**
     * 12-4: 「翌日」を押下した時に次の日の勤怠情報が正しく表示される
     */
    public function test_admin_attendance_list_transition_to_next_day_with_data()
    {
        $today = Carbon::create(2026, 4, 15);
        $tomorrow = $today->copy()->addDay();
        $this->travelTo($today);

        // 翌日のテストデータ（3名分）
        $testData = [
            [
                'name'           => 'ユーザーA',
                'in'             => '07:30:00', // DB流し込み用
                'out'            => '16:30:00',
                'check_in_disp'  => '07:30', // 表示チェック用
                'check_out_disp' => '16:30',
                'rest'           => '1:00',
                'work'           => '8:00'
            ],
            [
                'name'  => 'ユーザーB',
                'in'    => '13:00:00',
                'out'   => '22:00:00',
                'check_in_disp'  => '13:00',
                'check_out_disp' => '22:00',
                'rest'           => '1:00',
                'work'           => '8:00'
            ],
            [
                'name'  => 'ユーザーC',
                'in'    => '11:00:00',
                'out'   => '20:00:00',
                'check_in_disp'  => '11:00',
                'check_out_disp' => '20:00',
                'b_start' => null,
                'b_end'   => null,
                'rest'           => '1:00',
                'work'           => '8:00'
            ],
        ];

        // 翌日のデータを作成
        foreach ($testData as $data) {
            $user = User::factory()->create(['name' => $data['name'], 'role' => 1]);
            $attendance = $user->attendances()->create([
                'date' => $tomorrow,
                'check_in' => $data['in'],
                'check_out' => $data['out'],
                'status' => 3,
            ]);
            $attendance->rests()->create([
                'break_start' => '14:00:00', // 適当な1時間休憩
                'break_end' => '15:00:00',
            ]);
        }

        // 翌日の日付パラメータ付きでアクセス（「翌日」ボタンの挙動）
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list', ['date' => $tomorrow->format('Y-m-d')]));

        $response->assertStatus(200);
        $response->assertSee($tomorrow->format('Y年m月d日') . 'の勤怠');

        // 各ユーザーのデータが正確に表示されているかループで検証
        foreach ($testData as $data) {
            $response->assertSeeInOrder([
                $data['name'],
                $data['check_in_disp'],
                $data['check_out_disp'],
                $data['rest'],
                $data['work'],
            ]);
        }
    }
}
