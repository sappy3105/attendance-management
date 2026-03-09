<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Providers\RouteServiceProvider;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // ログインしたユーザーがまだメール認証を終えていない場合
        if (! Auth::user()->hasVerifiedEmail()) {
            // 認証誘導画面へ強制リダイレクト
            return redirect()->route('verification.notice');
        }

        // 認証済みなら、マイリストタブへ
        return redirect(RouteServiceProvider::HOME);
    }
}
