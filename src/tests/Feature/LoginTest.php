<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 2-0: ログイン画面が正しく表示されるか
     */

    public function test_staff_login_view_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * 2-1: メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_staff_email_is_required()
    {
        //ユーザーを登録する
        User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * 2-2: パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_staff_password_is_required()
    {
        //ユーザーを登録する
        User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * 2-3: 入力情報が間違っている場合、バリデーションメッセージが表示される
     */
    public function test_staff_login_fails_with_invalid_credentials()
    {
        //ユーザーを登録する
        User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }


    /**
     * 3-0: ログイン画面が正しく表示されるか
     */

    public function test_admin_login_view_can_be_rendered()
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    /**
     * 3-1: メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_email_is_required()
    {
        // 管理者を作成
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'admin123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * 3-2: パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_password_is_required()
    {
        // 管理者を作成
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * 3-3: 入力情報が間違っている場合、バリデーションメッセージが表示される
     */
    public function test_admin_login_fails_with_invalid_credentials()
    {
        // 管理者を作成
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'admin123',
        ]);

        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
}
