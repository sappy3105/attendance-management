@extends('layouts.default')

@section('content')
    <div class="attendance__content">
        {{-- ステータス表示 --}}
        <div class="attendance__status">
            @if (!$attendance)
                勤務外
            @elseif($attendance->status === 2)
                出勤中
            @elseif($attendance->status === 3)
                休憩中
            @elseif($attendance->status === 4)
                退勤済
            @endif
        </div>

        {{-- 現在の日付 --}}
        <div class="attendance__date">
            {{ now()->isoFormat('YYYY年M月D日(ddd)') }}
        </div>

        {{-- 現在の時刻 --}}
        <div class="attendance__time">
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
            @elseif($attendance->status === 2)
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
            @elseif($attendance->status === 3)
                <div class="attendance__panel">
                    <form action="/attendance/break-end" method="post">
                        @csrf
                        <button class="attendance__button-rest" type="submit">休憩戻</button>
                    </form>
                </div>

                {{-- 4. 退勤後の場合：メッセージ表示 --}}
            @elseif($attendance->status === 4)
                <div class="attendance__message">
                    お疲れ様でした。
                </div>
            @endif
            {{-- TODO: 今後、出勤中なら「休憩」「退勤」ボタンを出す処理を追加します --}}
        </div>
    </div>
@endsection
