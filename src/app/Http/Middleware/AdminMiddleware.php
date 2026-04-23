<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ログインしていて、かつ role が 2 (管理者) でなければ 403エラー
        if (auth()->check() && auth()->user()->role !== 2) {
            abort(403, 'このページへのアクセス権限がありません。');
        }
        return $next($request);
    }
}
