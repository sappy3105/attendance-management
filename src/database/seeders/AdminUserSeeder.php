<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'], // 検索条件
            [
                'name' => '管理者 太郎',
                'password' => Hash::make('admin_pass'),
                'role' => '2', // 2: 管理者
                'email_verified_at' => now(), //管理者はメール認証済みで登録
            ]
        );
    }
}
