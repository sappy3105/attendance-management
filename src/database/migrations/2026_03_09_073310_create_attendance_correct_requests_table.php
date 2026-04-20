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
        Schema::create('attendance_correct_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 修正後の値を保持（承認されるまで attendances テーブルは書き換えないため）
            $table->date('date');
            $table->time('check_in');
            $table->time('check_out');

            $table->text('remarks');// 備考（申請理由）
            $table->tinyInteger('status')->default(1)->comment('1:承認待ち, 2:承認済み'); // 申請状態
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correct_requests');
    }
};
