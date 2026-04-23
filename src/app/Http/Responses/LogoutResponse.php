<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // フォームから送られてきた'logout_type'を確認し、'admin'ならば管理者用ログインページへ
        if ($request->input('logout_type') === 'admin') {
            return redirect()->route('admin.login');
        }

        // それ以外は一般ユーザー用のログイン画面へ
        return redirect('/login');
    }
}
