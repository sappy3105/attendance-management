<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\AdminAttendanceController;

class RoleRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 管理者の場合 → AdminAttendanceController のメソッドを呼ぶ
        if ($user && $user->role === 2) {
            $result = app(AdminAttendanceController::class)->showRequestList($request);

            // もし戻り値が View なら response 化する
            return response($result);
        }

        // 一般ユーザーの場合、そのまま次の処理（AttendanceController）へ
        return $next($request);
    }
}
