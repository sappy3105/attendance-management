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
        'status' => 'integer',
    ];

    public function restCorrectRequests()
    {
        return $this->hasMany(RestCorrectRequest::class);
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
