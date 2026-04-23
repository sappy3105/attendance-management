<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestCorrectRequest>
 */
class RestCorrectRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_correct_request_id' => AttendanceCorrectRequest::factory(),
            'break_start' => '12:15',
            'break_end' => '13:15',
        ];
    }
}
