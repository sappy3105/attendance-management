<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class StaffUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffNames = ['田中 一郎', '鈴木 二郎', '高橋 三郎', '渡辺 四郎', '伊藤 五郎', '山本 六郎'];

        foreach ($staffNames as $index => $name) {
            $num = $index + 1;
            User::updateOrCreate(
                ['email' => "staff{$num}@example.com"], // 検索条件
                [
                    'name' => $name,
                    'password' => Hash::make('staff_pass'),
                    'role' => '1', // 1: スタッフ
                    'email_verified_at' => now(), // テスト用にメール認証済みにしておく
                ]
            );
        }
    }
}
