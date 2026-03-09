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
        Schema::create('rests', function (Blueprint $table) {
            $table->id();

            // どの勤怠データに紐づく休憩か
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();

            // 休憩開始・終了時間
            $table->time('start_time');
            $table->time('end_time')->nullable(); // 休憩中は null になるため

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rests');
    }
};
