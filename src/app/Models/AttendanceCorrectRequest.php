<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'date',
        'check_in',
        'check_out',
        'remarks',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'immutable_datetime:H:i',
        'check_out' => 'immutable_datetime:H:i',
    ];

    public function restCorrectRequests()
    {
        // 第二引数は RestCorrectRequest テーブルにある外部キー名を指定します
        return $this->hasMany(RestCorrectRequest::class, 'attendance_correct_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
