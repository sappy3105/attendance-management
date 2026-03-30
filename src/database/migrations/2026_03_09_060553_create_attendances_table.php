<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            // 誰の勤怠か
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 日付（勤怠一覧や月次表示の検索に使用）
            $table->date('date');

            // ステータス管理（1:勤務外, 2:出勤中, 3:休憩中, 4:退勤済）
            $table->tinyInteger('status')->default(1)->comment('1:出勤中, 2:休憩中, 3:退勤済');

            // 出勤・退勤時間（秒単位まで保持できる time 型が扱いやすいです）
            $table->time('check_in');
            $table->time('check_out')->nullable();

            // 備考（管理者修正時や申請時に必要）
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
