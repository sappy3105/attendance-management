<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

use App\Http\Requests\LoginRequest as MyLoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

use Laravel\Fortify\Fortify;
use App\Http\Responses\LoginResponse as MyLoginResponse;
use Laravel\Fortify\Contracts\LoginResponse as FortifyLoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

use App\Http\Responses\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ログインバリデーションの差し替え
        $this->app->singleton(FortifyLoginRequest::class, MyLoginRequest::class);

        // ログインレスポンス（リダイレクト先）を自作のものに差し替え
        $this->app->singleton(FortifyLoginResponse::class, MyLoginResponse::class);

        // 新規登録リクエストの差し替え
        // $this->app->singleton(FortifyRegisterRequest::class, MyRegisterRequest::class);

        // 新規登録後のレスポンス（メール認証誘導画面へ飛ばす設定）
        // $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

        // ログイン後のレスポンス（未認証なら誘導画面、済みならHOMEへ）
        // $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        // メール認証完了後のレスポンス（プロフィール編集画面へ）
        // $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);

        // ログアウト後のリダイレクト先
        // $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
        //     public function toResponse($request)
        //     {
        //         if (request()->is('admin/*') || request()->is('admin')) {
        //             return redirect('/admin/login');
        //         }
        //         return redirect('/login');
        //     }
        // });

        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ユーザー作成
        Fortify::createUsersUsing(CreateNewUser::class);

        // 新規登録画面のView（スタッフのみ）
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログインViewの切り替え
        Fortify::loginView(function () {
            if (request()->is('admin/login')) {
                return view('admin.auth.login');
            }
            return view('auth.login');
        });

        //メール認証機能
        // Fortify::verifyEmailView(function () {
        //     return view('auth.verify-email');
        // });

        // ログイン制限（RateLimiter）
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
