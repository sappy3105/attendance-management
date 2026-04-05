@extends('admin.layouts.default') {{-- 管理者用レイアウト --}}

@section('content')
    <div class="attendance-list"> {{-- 一般ユーザーと同じクラス名 --}}
        <h1 class="attendance-list__heading">{{ $user->name }}の勤怠</h1>

        <div class="attendance-list__nav">
            {{-- URLにスタッフIDを含めるよう修正 --}}
            <a href="{{ url("/admin/attendance/staff/{$user->id}?month=" . $prevMonth) }}" class="nav-button">← 前月</a>

            <div class="month-picker">
                <input type="month" id="month-input" class="hidden-month-input" value="{{ $currentMonth->format('Y-m') }}"
                    onchange="location.href='/admin/attendance/staff/{{ $user->id }}?month='+this.value">
                <label for="month-input" class="month-display">
                    {{ $currentMonth->format('Y/m') }}
                </label>
            </div>

            <a href="{{ url("/admin/attendance/staff/{$user->id}?month=" . $nextMonth) }}" class="nav-button">翌月 →</a>
        </div>

        <table class="attendance-table"> {{-- 一般ユーザーと同じクラス名 --}}
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
                    @php $attendance = $attendances->get($day->format('Y-m-d')); @endphp
                    <tr>
                        <td>{{ $day->isoFormat('MM/DD(ddd)') }}</td>
                        <td>{{ $attendance?->check_in?->format('H:i') ?? '' }}</td>
                        <td>{{ $attendance?->check_out?->format('H:i') ?? '' }}</td>
                        <td>{{ $attendance ? $attendance->getTotalRestTime() : '' }}</td>
                        <td>{{ $attendance ? $attendance->getTotalWorkTime() : '' }}</td>
                        <td>
                            @if ($day->isPast() || $day->isToday())
                                @if ($attendance)
                                    <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}"
                                        class="attendance-table__detail-link">詳細</a>
                                @else
                                    {{-- レコードがない場合も管理者が作成できるように 'new' を渡す --}}
                                    <a href="{{ route('admin.attendance.detail', ['id' => 'new', 'user_id' => $user->id, 'date' => $day->format('Y-m-d')]) }}"
                                        class="attendance-table__detail-link">詳細</a>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="attendance-list__actions">
            {{-- 通常時：修正ボタンを表示 --}}
            <a href="{{ route('admin.export', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}"
                class="attendance-list__export-link">
                CSV出力
            </a>
        </div>
    </div>
@endsection
