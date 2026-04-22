<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\AdminAttendanceController;
use Illuminate\Contracts\Support\Responsable;

class RoleRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 現在の認証ユーザーを取得
        $user = auth()->user();

        // 管理者の場合 → AdminAttendanceController のメソッドを呼ぶ
        if ($user && $user->role === 2 && $request->routeIs('attendance.requests')) {
            // 管理者用コントローラのメソッドを実行
            $result = app(AdminAttendanceController::class)->showRequestList($request);

            // 戻り値が View や JsonResource などの場合、Response オブジェクトに変換
            if ($result instanceof Responsable) {
                return $result->toResponse($request);
            }

            // それ以外（文字列など）の場合は response() で包む
            return response($result);
        }

        // 一般ユーザーの場合、そのまま次の処理（AttendanceController）へ
        return $next($request);
    }
}
