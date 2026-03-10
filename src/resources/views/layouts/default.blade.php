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
            <div class="header__heading">
                <a href="/">
                    <img src="{{ asset('images/COACHTECH_headerlogo.png') }}" alt="COACHTECH" class="header__logo">
                </a>
            </div>

            {{-- ログインと新規登録画面以外表示する --}}
            @unless (request()->routeIs('login', 'register'))
                <nav class="header__nav">
                    <ul class="header__nav-list">
                        @auth
                            <li><a href="" class="header__nav-link">勤怠</a></li>
                            <li><a href="" class="header__nav-link">勤怠一覧</a></li>
                            <li><a href="" class="header__nav-link">申請</a></li>
                            <li>
                                {{-- ログインしている時 --}}
                                <form action="/logout" method="POST">
                                    @csrf
                                    <button type="submit" class="header__nav-link">ログアウト</button>
                                </form>
                            </li>
                        @endauth
                    </ul>
                </nav>
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
