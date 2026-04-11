<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceCorrectRequest>
 */
class AttendanceCorrectRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'check_in' => '09:30',
            'check_out' => '18:30',
            'remarks' => '打刻ミス修正のため',
            'status' => 1, // 承認待ち
        ];
    }
}
