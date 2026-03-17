<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Attendance;
use App\Models\Rest;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // 1. 外部キー制約を一時的に無効化
        Schema::disableForeignKeyConstraints();

        // 2. 空にするテーブルを指定
        Rest::truncate();
        Attendance::truncate();
        User::truncate();

        // 3. 外部キー制約を元に戻す
        Schema::enableForeignKeyConstraints();

        // 4. 各シーダーを実行してデータを注入
        $this->call([
            AdminUserSeeder::class,
            StaffUserSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
