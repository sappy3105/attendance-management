@extends('layouts.default', ['mainClass' => 'main--white'])

@section('content')
    <div class="register-form__content">
        <h1 class="register-form__heading">会員登録</h1>
        <div class="register-form__inner">
            <form class="register-form__form" action="/register" method="post" novalidate>
                @csrf
                <div class="register-form__group">
                    <label class="register-form__label" for="name">名前</label>
                    <input class="register-form__input" type="text" name="name" id="name"
                        value="{{ old('name') }}" autofocus>
                    <div class="register-form__error-message">
                        @error('name')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
                <div class="register-form__group">
                    <label class="register-form__label" for="email">メールアドレス</label>
                    <input class="register-form__input" type="email" name="email" id="email"
                        value="{{ old('email') }}" autofocus>
                    <div class="register-form__error-message">
                        @error('email')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
                <div class="register-form__group">
                    <label class="register-form__label" for="password">パスワード</label>
                    <input class="register-form__input" type="password" name="password" id="password">
                    <div class="register-form__error-message">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
                <div class="register-form__group">
                    <label class="register-form__label" for="password">パスワード確認</label>
                    <input class="register-form__input" type="password" name="password_confirmation"
                        id="password_confirmation">
                    <div class="register-form__error-message">
                        @error('password_confirmation')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
                <button class="register-form__button-submit" type="submit">登録する</button>
            </form>
            <div class="login__link">
                <a class="login__button" href="/login">ログインはこちら</a>
            </div>
        </div>
    </div>
@endsection
