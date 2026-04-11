<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // 「password」のハッシュ値
            'role' => 1,//一般ユーザー
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * 管理者ユーザーを定義
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 2,
        ]);
    }

    // ↓後ほど削除するかも
    /**
     * Indicate that the model's email address should be unverified.
     *
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
