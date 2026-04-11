<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1-0 会員登録画面が正しく表示されるか
     */
    public function test_register_view_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * 1-1 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /**
     * 1-2 メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '', // 空にする
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * 1-3 パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_password_minimum_length()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123', // 7文字
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /**
     * 1-4 パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password', // 一致させない
        ]);

        $response->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);
    }

    /**
     * 1-5 パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '', // 空にする
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }


    /**
     * 1-6 フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_user_can_register_successfully()
    {
        // メール送信をシミュレート（実際には送らない）
        Notification::fake();

        // 1. POST送信
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 2. データベースに保存されているか確認
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'newuser@example.com',
            'role' => 1,
        ]);

        // 作成されたユーザーを取得
        $user = User::where('email', 'newuser@example.com')->first();

        // 3. 認証メールが送信されたことを確認
        Notification::assertSentTo($user, VerifyEmail::class);

        // 4. メール認証を完了させる
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 認証URLへアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        // 5. 勤怠登録画面へのリダイレクト確認
        $response->assertRedirect(route('attendance.index'));

        // 6. ログイン状態かつ、メール認証済みであることを確認
        $this->assertAuthenticated();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
