<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        // 'check_in' => 'datetime',
        // 'check_out' => 'datetime',
    ];

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩時間の合計を「H:i」形式で返す
     */
    public function getTotalRestTime()
    {
        $totalMinutes = 0;
        foreach ($this->rests as $rest) {
            if ($rest->break_start && $rest->break_end) {
                $start = Carbon::parse($rest->break_start);
                $end = Carbon::parse($rest->break_end);
                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 実働時間（退勤 - 出勤 - 休憩）を「H:i」形式で返す
     */
    public function getTotalWorkTime()
    {
        if (!$this->check_in || !$this->check_out) {
            return '';
        }

        $start = Carbon::parse($this->check_in);
        $end = Carbon::parse($this->check_out);

        // 滞在総時間（分）
        $totalStayMinutes = $start->diffInMinutes($end);

        // 休憩総時間（分）
        $totalRestMinutes = 0;
        foreach ($this->rests as $rest) {
            if ($rest->break_start && $rest->break_end) {
                $totalRestMinutes += Carbon::parse($rest->break_start)->diffInMinutes(Carbon::parse($rest->break_end));
            }
        }

        $workMinutes = $totalStayMinutes - $totalRestMinutes;

        // マイナスにならないよう調整
        if ($workMinutes < 0) $workMinutes = 0;

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;
        return sprintf('%d:%02d', $hours, $minutes);
    }
}
