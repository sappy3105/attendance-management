<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>COACHTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <h1 class="header__heading">
                <a href="/">
                    <img src="{{ asset('images/COACHTECH_headerlogo.png') }}" alt="COACHTECH" class="header__logo">
                </a>
            </h1>

            {{-- ログインと新規登録画面以外表示する --}}
            @unless (request()->routeIs('login', 'register'))
                <div class="header__nav">
                    <form action="{{ request()->url() }}" method="GET" class="header__search">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="なにをお探しですか？"
                            class="header__search-input">
                        @if (Route::is('mypage'))
                            {{-- マイページにいるときは 'page' を送る --}}
                            <input type="hidden" name="page" value="{{ request('page', 'sell') }}">
                        @elseif (Route::is('item.index'))
                            {{-- 商品一覧ページにいるときは 'tab' を送る --}}
                            <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
                        @endif
                    </form>
                    <nav>
                        <ul class="header__nav-list">
                            <li>
                                {{-- ログインしている時 --}}
                                @auth
                                    <form action="/logout" method="POST">
                                        @csrf
                                        <button type="submit" class="header__nav-link">ログアウト</button>
                                    </form>
                                @endauth

                                {{-- ログインしていない時 --}}
                                @guest
                                    <a href="/login" class="header__nav-link">ログイン</a>
                                @endguest
                            </li>
                            <li><a href="/mypage" class="header__nav-link">マイページ</a></li>
                            <li><a href="/sell" class="header__nav-btn">出品</a></li>
                        </ul>
                    </nav>
                </div>
            @endunless
        </div>
    </header>

    <main class="main">
        <div class="main__inner">
            @yield('content')
        </div>
    </main>

</body>

</html>
