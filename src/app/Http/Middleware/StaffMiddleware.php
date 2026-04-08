<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role !== 1) {
            // 管理者が一般画面に来た場合は、 403エラー
            abort(403, 'このページへのアクセス権限がありません。');
            // return redirect()->route('admin.attendance.list');管理者用勤怠一覧へリダイレクト
            // あるいは 403 にしたい場合は abort(403);
        }
        return $next($request);
    }
}
