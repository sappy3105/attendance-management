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
        'check_in' => 'immutable_datetime:H:i',
        'check_out' => 'immutable_datetime:H:i',
    ];

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function correctRequests()
    {
        return $this->hasMany(AttendanceCorrectRequest::class);
    }

    public function restCorrectRequests()
    {
        return $this->hasMany(RestCorrectRequest::class);
    }

    /**
     * 休憩時間の合計を「分（数値）」で返す（計算用）
     */
    public function getTotalRestMinutes(): int
    {
        $totalMinutes = 0; // 1. 合計分を保持する変数を0で初期化
        foreach ($this->rests ?? collect() as $rest) { // 2. この勤怠に紐づく休憩レコードを1つずつ取り出す。し休憩データが空っぽ（null）なら、空の箱（空のコレクション）を用意する
            if ($rest->break_start && $rest->break_end) { // 3. 開始と終了の両方の時刻がある場合のみ計算する
                // キャストをH:iにしている場合、Carbon::parseが必要
                $start = Carbon::parse($rest->break_start); // 4. 文字列の開始時刻をCarbonオブジェクトに変換
                $end = Carbon::parse($rest->break_end); // 5. 文字列の終了時刻をCarbonオブジェクトに変換
                $totalMinutes += $start->diffInMinutes($end); // 6. 開始と終了の差（分）を計算して合計に足す
            }
        }
        return $totalMinutes; // 7. 全ての休憩を足し合わせた合計分を返す
    }

    /**
     * 休憩時間の合計を「H:i」形式で返す（表示用）
     */
    public function getTotalRestTime()
    {
        // 出勤・退勤のどちらかがなければ空文字を返す
        if (!$this->check_in || !$this->check_out) { // 1. 出勤か退勤のどちらかが欠けていれば（退勤前など）
            return ''; // 2. 何も計算せずに空文字を返す（エラー防止）
        }

        $totalMinutes = $this->getTotalRestMinutes(); // 1. 上記のメソッドを使い、合計分を取得
        $hours = floor($totalMinutes / 60); // 2. 合計分を60で割り、小数点以下を切り捨てて「時間」を出す
        $minutes = $totalMinutes % 60; // 3. 合計分を60で割った「余り」を「分」として出す
        return sprintf('%d:%02d', $hours, $minutes); // 4. 「時間:0埋めした2桁の分」という形式の文字列にして返す
    }

    /**
     * 実働時間（退勤 - 出勤 - 休憩）を「H:i」形式で返す（表示用）
     */
    public function getTotalWorkTime()
    {
        // 出勤・退勤のどちらかがなければ空文字を返す
        if (!$this->check_in || !$this->check_out) { // 1. 出勤か退勤のどちらかが欠けていれば（退勤前など）
            return ''; // 2. 何も計算せずに空文字を返す（エラー防止のガード節）
        }

        $start = Carbon::parse($this->check_in); // 3. 出勤時刻をCarbonオブジェクトに変換
        $end = Carbon::parse($this->check_out); // 4. 退勤時刻をCarbonオブジェクトに変換

        // 5. (出勤と退勤の差分) から (休憩の合計分) を引いて、実働の合計分を出す
        $workMinutes = $start->diffInMinutes($end) - $this->getTotalRestMinutes();

        $hours = floor($workMinutes / 60); // 6. 実働分を60で割り、時間を出す
        $minutes = $workMinutes % 60; // 7. 実働分を60で割った余りを出す
        return sprintf('%d:%02d', $hours, $minutes); // 8. 形式を整えた文字列で返す
    }
}
