<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'staff@example.com'], // 検索条件
            [
                'name' => 'テスト スタッフ',
                'password' => Hash::make('staff_pass'),
                'role' => '1', // 1: スタッフ
                'email_verified_at' => now(), // テスト用にメール認証済みにしておく
            ]
        );
    }
}
