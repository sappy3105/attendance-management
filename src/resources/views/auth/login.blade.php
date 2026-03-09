@extends('layouts.default')

@section('content')
    <div class="login-form__content"> {{-- 共通のスタイルを適用 --}}
        <h2 class="login-form__heading">ログイン</h2>

        <form action="{{ route('login') }}" method="post" novalidate>
            @csrf

            <div class="login-form__group">
                <label class="login-form__label" for="email">メールアドレス</label>
                <input class="login-form__input" type="email" name="email" id="email" value="{{ old('email') }}" autofocus>
                <div class="login-form__error-message">
                    @error('email')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <div class="login-form__group">
                <label class="login-form__label" for="password">パスワード</label>
                <input class="login-form__input" type="password" name="password" id="password">
                <div class="login-form__error-message">
                    @error('password')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <button class="login-form__button-submit" type="submit">ログインする</button>
        </form>

        <div class="register__link">
            <a class="register__button" href="/register">会員登録はこちら</a>
        </div>
    </div>
@endsection
