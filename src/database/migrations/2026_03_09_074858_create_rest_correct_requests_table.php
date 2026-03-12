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
        Schema::create('rest_correct_requests', function (Blueprint $table) {
            $table->id();
            // どの「修正申請」に紐づく休憩データか
            $table->foreignId('attendance_correct_request_id')->constrained()->cascadeOnDelete();

            // 修正後の休憩開始・終了時間
            $table->time('break_start');
            $table->time('break_end');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rest_correct_requests');
    }
};
