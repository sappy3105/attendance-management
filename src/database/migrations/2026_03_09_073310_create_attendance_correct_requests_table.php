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

            // どの勤怠データに対する修正依頼か
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();

            // 申請者（一般ユーザー）
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 修正後の値を保持（承認されるまで attendances テーブルは書き換えないため）
            $table->date('date');
            $table->time('check_in');
            $table->time('check_out');

            // 休憩時間は「合計時間」で持つか、または別途「休憩修正テーブル」を作るか検討が必要ですが、
            // 簡易的には「修正後の休憩時間合計」などを持たせる形でも要件は満たせます。
            // 今回は要件に合わせ、備考（申請理由）を追加します。
            $table->text('remarks');

            // 申請状態（1:承認待ち, 2:承認済み）
            $table->tinyInteger('status')->default(1)->comment('1:承認待ち, 2:承認済み');

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
