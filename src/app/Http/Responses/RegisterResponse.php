<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        // もしユーザーがまだメール認証を終えていないなら
        if (! Auth::user()->hasVerifiedEmail()) {
            // 認証誘導画面を表示するルートへ飛ばす
            return redirect()->route('verification.notice');
        }

        // すでに認証済みならプロフィールへ
        return redirect()->route('profile.edit');
    }
}
