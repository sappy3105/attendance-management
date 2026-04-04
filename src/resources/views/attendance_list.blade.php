@extends('layouts.default')

@section('content')
    <div class="attendance-list">
        <h1 class="attendance-list__heading">勤怠一覧</h1>

        {{-- ページネーション & 月選択 --}}
        <div class="attendance-list__nav">
            <a href="{{ url('/attendance/list?month=' . $prevMonth) }}" class="nav-button">← 前月</a>

            <div class="month-picker">
                <input type="month" id="month-input" class="hidden-month-input" value="{{ $currentMonth->format('Y-m') }}"
                    onchange="location.href='/attendance/list?month='+this.value">
                <label for="month-input" class="month-display">
                    <img src="{{ asset('images/calendar_icon.svg') }}" alt="カレンダー" class="calendar-trigger-icon">
                    {{ $currentMonth->format('Y/m') }}
                </label>
            </div>

            <a href="{{ url('/attendance/list?month=' . $nextMonth) }}" class="nav-button">翌月 →</a>
        </div>

        {{-- 勤怠テーブル --}}
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($calendar as $day)
                    @php
                        $attendance = $attendances->get($day->format('Y-m-d'));
                    @endphp

                    <tr>
                        <td>{{ $day->isoFormat('MM/DD(ddd)') }}</td>

                        {{-- 出勤時刻 --}}
                        <td>
                            {{ $attendance && $attendance->check_in ? $attendance->check_in->format('H:i') : '' }}
                        </td>

                        {{-- 退勤時刻 --}}
                        <td>
                            {{ $attendance && $attendance->check_out ? $attendance->check_out->format('H:i') : '' }}
                        </td>

                        {{-- 休憩合計時間 --}}
                        <td>
                            {{ $attendance ? $attendance->getTotalRestTime() : '' }}
                        </td>

                        {{-- 勤務合計時間 --}}
                        <td>
                            {{ $attendance ? $attendance->getTotalWorkTime() : '' }}
                        </td>

                        <td>
                            @if ($attendance)
                                {{-- レコードがある場合はそのIDを渡す --}}
                                <a href="{{ route('attendance.detail', ['id' => $attendance->id]) }}"
                                    class="attendance-table__detail-link">詳細</a>
                            @else
                                {{-- レコードがない場合は ID=new と日付を渡す --}}
                                <a href="{{ route('attendance.detail', ['id' => 'new', 'date' => $day->format('Y-m-d')]) }}"
                                    class="attendance-table__detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
