@extends('layouts.default')

@section('content')
    <div class="verify__container">
        <div class="verify__content">
            <p class="verify__text">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <div class="verify__button-wrapper">
                <a href="{{ config('services.mail_dashboard') }}" target="_blank" rel="noopener noreferrer"
                    class="verify__button">
                    認証はこちらから
                </a>
            </div>

            {{-- 再送フォーム --}}
            <form method="POST" action="{{ route('verification.send') }}" class="verify__re-send-form">
                @csrf
                <button type="submit" class="verify__re-send-link">
                    認証メールを再送する
                </button>
            </form>

            {{-- 再送完了メッセージ --}}
            @if (session('status') == 'verification-link-sent')
                <p class="verify__alert">
                    新しい認証リンクを送信しました。
                </p>
            @endif
        </div>
    </div>
@endsection
