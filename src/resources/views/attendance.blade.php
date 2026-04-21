@extends('layouts.default')

@section('content')
    <div class="attendance__content">
        {{-- ステータス表示 --}}
        <div class="attendance__status">
            @if (!$attendance)
                勤務外
            @elseif($attendance->status === 1)
                出勤中
            @elseif($attendance->status === 2)
                休憩中
            @elseif($attendance->status === 3)
                退勤済
            @endif
        </div>

        {{-- 現在の日付 --}}
        <div class="attendance__date">
            {{ now()->isoFormat('YYYY年M月D日(ddd)') }}
        </div>

        {{-- 現在の時刻 --}}
        <div class="attendance__time" id="current-time">
            {{ now()->format('H:i') }}
        </div>

        {{-- 打刻フォーム --}}
        <div class="attendance__panel">
            {{-- 1. 勤務外（データなし）の場合 --}}
            @if (!$attendance)
                <form action="/attendance/check-in" method="post">
                    @csrf
                    {{-- 勤務外（データなし）の時のみ出勤ボタンを表示 --}}
                    <button class="attendance__button-submit" type="submit">出勤</button>
                </form>

                {{-- 2. 出勤中の場合：退勤と休憩入を表示 --}}
            @elseif($attendance->status === 1)
                <div class="attendance__button-group">
                    <form action="/attendance/check-out" method="post">
                        @csrf
                        <button class="attendance__button-submit" type="submit">退勤</button>
                    </form>
                    <form action="/attendance/break-start" method="post">
                        @csrf
                        <button class="attendance__button-rest" type="submit">休憩入</button>
                    </form>
                </div>

                {{-- 3. 休憩中の場合：休憩戻を表示 --}}
            @elseif($attendance->status === 2)
                <form action="/attendance/break-end" method="post">
                    @csrf
                    <button class="attendance__button-rest" type="submit">休憩戻</button>
                </form>

                {{-- 4. 退勤後の場合：メッセージ表示 --}}
            @elseif($attendance->status === 3)
                <div class="attendance__message">
                    お疲れ様でした。
                </div>
            @endif
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            // HTML内の id="current-time" と書かれた場所を探して、中身を「時:分」に書き換える
            document.getElementById('current-time').textContent = `${hours}:${minutes}`;
        }

        // 1秒（1000ミリ秒）ごとにupdateClock関数を実行
        setInterval(updateClock, 1000);

        // ページを開いた瞬間に1回目を実行する
        updateClock();
    </script>
@endsection
