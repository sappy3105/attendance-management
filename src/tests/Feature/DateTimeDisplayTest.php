<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 4-1: 勤怠打刻画面に現在の日時がUIと同じ形式で表示される
     */
    public function test_current_datetime_is_displayed_on_attendance_page()
    {
        // 1. ユーザーを登録・作成
        $user = User::factory()->create();

        // 2. 現在時刻を固定する（2026年4月11日 18:30）
        $fixedNow = Carbon::create(2026, 4, 11, 18, 30, 0);
        $this->travelTo($fixedNow);

        // 3. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('attendance.index'));

        // 4. 画面上に表示されている日時情報が現在日時と一致するか確認
        $response->assertStatus(200);

        // UIの形式が「2026年4月11日(土)」のような形式であれば、それに合わせて検証
        // お使いのBladeでの表示形式に合わせて調整してください
        $response->assertSee($fixedNow->isoFormat('YYYY年M月D日'));
        $response->assertSee('土');
        $response->assertSee($fixedNow->format('H:i'));
    }
}
