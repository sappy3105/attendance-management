<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 16-1 会員登録後、認証メールが送信される
     */
    public function test_verification_email_is_sent_after_registration()
    {
        // メール送信をシミュレート
        Notification::fake();

        // 会員登録を実行
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 登録後にメール認証誘導画面にリダイレクトされることを確認
        $response->assertRedirect('/email/verify');

        // ユーザーを取得
        $user = User::where('email', 'test@example.com')->first();

        // VerifyEmail通知が送られたことを確認
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 16-2 メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function test_user_can_navigate_to_mail_client_from_verify_prompt()
    {
        // 未認証ユーザーを作成してログイン
        $user = User::factory()->unverified()->create();

        // テスト用にURLをセット
        $expectedUrl = 'https://mailtrap.io/inboxes';
        config(['services.mail_dashboard' => $expectedUrl]);

        // メール認証誘導画面を表示
        $response = $this->actingAs($user)->get('/email/verify');
        $response->assertStatus(200);
        $response->assertSee('href="' . $expectedUrl . '"', false);
        $response->assertSee('認証はこちらから');
    }

    /**
     * 16-3 メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_user_is_redirected_to_profile_setup_after_verification()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->unverified()->create();

        // ユーザー用の署名付き認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 生成した認証URLにアクセスして認証を完了させる
        $response = $this->actingAs($user)->get($verificationUrl);

        // 勤怠登録画面へリダイレクトされることを確認
        $response->assertRedirect(route('attendance.index'));

        // ユーザーのメールが認証済み（email_verified_at に値が入っている）ことを確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
