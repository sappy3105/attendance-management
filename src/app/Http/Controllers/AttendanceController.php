<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        // ここで views/attendance.blade.php を呼び出す
        return view('attendance');
    }
}
