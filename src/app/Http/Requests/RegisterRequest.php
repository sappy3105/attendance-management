<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // パスワードが「入力済み」かつ「8文字以上」か判定
        $password = $this->input('password');
        $isPasswordValid = !empty($password) && mb_strlen($password) >= 8;

        return [
            'name' => ['required', 'max:20'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            // パスワードのバリデーションをクリアしている時だけ、確認用のチェックを行う
            'password_confirmation' => array_filter([
                $isPasswordValid ? 'required' : null,
                $isPasswordValid ? 'min:8' : null,
                $isPasswordValid ? 'same:password' : null,
            ]),
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'お名前を入力してください',
            'name.max' => 'お名前は20文字以内で入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'email.unique' => 'このメールアドレスは既に登録されています',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password_confirmation.required' => 'パスワードと一致しません',
            'password_confirmation.min' => 'パスワードと一致しません',
            'password_confirmation.same' => 'パスワードと一致しません',
        ];
    }
}
