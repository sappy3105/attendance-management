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
        // if ($user->role === 'admin') {
        //     return redirect()->intended('/admin/attendance/list');
        // }

        // 2. 一般ユーザーの場合のみ、メール認証をチェック
        // if (!$user->hasVerifiedEmail()) {
        //     return redirect()->route('verification.notice');
        // }

        // 3. 認証済みの一般ユーザーは標準のHOMEへ
        // return redirect()->intended(RouteServiceProvider::HOME);

        // 管理者の場合←いったんの設定。認証画面できあがったら上記の記述に書き換える
        if ($user->role == '2') {
            return redirect()->intended('/admin/attendance/list');
        }

        // 一般ユーザーの場合（メール認証なしで直接リダイレクト）
        return redirect()->intended('/attendance');
    }
}
