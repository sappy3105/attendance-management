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
            {{ \Carbon\Carbon::now()->isoFormat('YYYY年M月D日(ddd)') }}
        </div>

        {{-- 現在の時刻 --}}
        <div class="attendance__time">
            {{ \Carbon\Carbon::now()->format('H:i') }}
        </div>

        {{-- 打刻フォーム --}}
        <div class="attendance__panel">
            <form action="/attendance/work-start" method="post">
                @csrf
                {{-- 勤務外（データなし）の時のみ出勤ボタンを表示 --}}
                @if (!$attendance)
                    <button class="attendance__button-submit" type="submit">出勤</button>
                @endif
            </form>

            {{-- TODO: 今後、出勤中なら「休憩」「退勤」ボタンを出す処理を追加します --}}
        </div>
    </div>
@endsection
