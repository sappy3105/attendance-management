<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // ログアウトを実行した元のページのURLを取得
        $referer = $request->headers->get('referer');

        // URLに 'admin' が含まれている場合は管理者のログイン画面へ
        if (str_contains($referer, '/admin')) {
            return redirect()->route('admin.login');
        }

        // それ以外はスタッフ用のログイン画面へ
        return redirect('/login');
    }
}
