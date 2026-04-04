@extends('admin.layouts.default')

@section('content')
    <div class="attendance-list">
        {{-- 要件：現在の日付を表示 --}}
        <h1 class="attendance-list__heading">{{ $currentDate->format('Y年m月d日') }}の勤怠</h1>

        {{-- 日付変更機能 --}}
        <div class="attendance-list__nav">
            <a href="{{ url('/admin/attendance/list?date=' . $prevDate) }}" class="nav-button">← 前日</a>

            <div class="month-picker">
                <input type="date" id="date-input" class="hidden-month-input" value="{{ $currentDate->format('Y-m-d') }}"
                    onchange="location.href='/admin/attendance/list?date='+this.value">
                <label for="date-input" class="month-display">
                    <img src="{{ asset('images/calendar_icon.svg') }}" alt="カレンダー" class="calendar-trigger-icon">
                    {{ $currentDate->format('Y/m/d') }}
                </label>
            </div>

            <a href="{{ url('/admin/attendance/list?date=' . $nextDate) }}" class="nav-button">翌日 →</a>
        </div>

        {{-- 勤怠テーブル --}}
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php
                        // そのユーザーの、その日の勤怠を取り出す
                        $attendance = $user->attendances->first();
                    @endphp

                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $attendance?->check_in ? $attendance->check_in->format('H:i') : '' }}</td>
                        <td>{{ $attendance?->check_out ? $attendance->check_out->format('H:i') : '' }}</td>
                        <td>{{ $attendance ? $attendance->getTotalRestTime() : '' }}</td>{{-- 休憩合計 --}}
                        <td>{{ $attendance ? $attendance->getTotalWorkTime() : '' }}</td>{{-- 勤務合計 --}}
                        <td>
                            @if ($attendance)
                                <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}"
                                    class="attendance-table__detail-link">詳細</a>
                            @else
                                {{-- IDがないので 'new' を渡し、誰のいつの分かを追加で送る --}}
                                <a href="{{ route('admin.attendance.detail', ['id' => 'new', 'user_id' => $user->id, 'date' => $currentDate->format('Y-m-d')]) }}"
                                    class="attendance-table__detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
