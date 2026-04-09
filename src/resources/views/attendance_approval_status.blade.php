@extends('layouts.default')

@section('content')
    <div class="attendance-detail">
        <h1 class="attendance-detail__heading">勤怠詳細（申請内容確認）</h1>

        <div class="attendance-detail__form">
            <table class="attendance-detail__table">
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">名前</th>
                    <td class="attendance-detail__value">
                        <span class="attendance-detail__user-name">{{ $attendanceCorrectRequest->user->name }}</span>
                    </td>
                </tr>

                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">日付</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__date-container">
                            <span class="attendance-detail__date-year">
                                {{ $attendanceCorrectRequest->date->format('Y年') }}
                            </span>
                            <span class="attendance-detail__date-separator"></span>
                            <span class="attendance-detail__date-month">
                                {{ $attendanceCorrectRequest->date->isoFormat('M月D日') }}
                            </span>
                        </div>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">出勤・退勤</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__time-group">
                            <span class="attendance-detail__text-time">
                                {{ $attendanceCorrectRequest->check_in?->format('H:i') ?? '' }}
                            </span>
                            <span class="attendance-detail__separator">〜</span>
                            <span class="attendance-detail__text-time">
                                {{ $attendanceCorrectRequest->check_out?->format('H:i') ?? '' }}
                            </span>
                        </div>
                    </td>
                </tr>

                @foreach ($attendanceCorrectRequest->restCorrectRequests as $index => $rest)
                    <tr class="attendance-detail__row">
                        <th class="attendance-detail__label">
                            休憩{{ $loop->iteration > 1 ? $loop->iteration : '' }}
                        </th>
                        <td class="attendance-detail__value">
                            <div class="attendance-detail__time-group">
                                <span class="attendance-detail__text-time">
                                    {{ $rest->break_start?->format('H:i') ?? '' }}
                                </span>
                                <span class="attendance-detail__separator">〜</span>
                                <span class="attendance-detail__text-time">
                                    {{ $rest->break_end?->format('H:i') ?? '' }}
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforeach

                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">備考</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__text-remarks">{!! nl2br(e($attendanceCorrectRequest->remarks)) !!}</div>
                    </td>
                </tr>
            </table>

            <div class="attendance-detail__actions">
                @if ($attendanceCorrectRequest->status === 1)
                    {{-- 承認待ち(1)の場合 --}}
                    <p class="attendance-detail__pending-msg">＊承認待ちのため修正はできません。</p>
                @else
                    {{-- 承認済み(2)の場合 --}}
                    <div class="attendance-detail__approved-label">承認済み</div>
                @endif
            </div>
        </div>
    </div>
@endsection
