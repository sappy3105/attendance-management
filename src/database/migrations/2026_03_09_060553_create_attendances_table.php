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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date'); // 日付
            $table->tinyInteger('status')->default(1)->comment('1:出勤中, 2:休憩中, 3:退勤済'); // ステータス管理
            $table->time('check_in')->nullable(); // 出勤時間
            $table->time('check_out')->nullable(); // 退勤時間
            $table->text('remarks')->nullable(); // 備考（管理者修正時や申請時に必要）
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
