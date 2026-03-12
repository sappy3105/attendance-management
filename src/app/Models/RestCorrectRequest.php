<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestCorrectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correct_request_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end'   => 'datetime',
    ];
}
