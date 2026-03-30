<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Providers\RouteServiceProvider;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        // 1. まず「管理者」かどうかを判定（管理者はメール認証をチェックせず進む）
        if ($user->role === '2') {
            return redirect()->intended('admin.attendance.list');
        }

        // 2. 一般ユーザーの場合のみ、未認証なら認証誘導画面へ
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // 3. 認証済みの一般ユーザーは打刻画面へ
        return redirect()->route('attendance.index');
    }
}
