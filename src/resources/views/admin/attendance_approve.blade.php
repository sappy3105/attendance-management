@extends('admin.layouts.default')

@section('content')
    <div class="attendance-detail">
        <h1 class="attendance-detail__heading">勤怠詳細</h1>

        <form action="{{ route('admin.attendance.approve', $correctRequest->id) }}" method="POST"
            class="attendance-detail__form">
            @csrf
            <table class="attendance-detail__table">
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">名前</th>
                    <td class="attendance-detail__value">
                        <span class="attendance-detail__user-name">{{ $correctRequest->user->name }}</span>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">日付</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__date-container">
                            <span class="attendance-detail__date-year">
                                {{ $correctRequest->date->format('Y年') }}
                            </span>
                            <span class="attendance-detail__date-separator"></span>
                            <span class="attendance-detail__date-month">
                                {{ $correctRequest->date->isoFormat('M月D日') }}
                            </span>
                        </div>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">出勤・退勤</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__time-group">
                            <span class="attendance-detail__text-time">
                                {{ $correctRequest->check_in?->format('H:i') ?? '' }}
                            </span>
                            <span class="attendance-detail__separator">〜</span>
                            <span class="attendance-detail__text-time">
                                {{ $correctRequest->check_out?->format('H:i') ?? '' }}
                            </span>
                        </div>
                    </td>
                </tr>

                @foreach ($correctRequest->restCorrectRequests as $index => $rest)
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
                        <div class="attendance-detail__text-remarks">{!! nl2br(e($correctRequest->remarks)) !!}</div>
                    </td>
                </tr>
            </table>

            <div class="attendance-detail__actions">
                @if ($correctRequest->status === 1)
                    {{-- 承認待ち(1)の場合：承認ボタンを表示 --}}
                    <button type="submit" class="attendance-detail__submit-button">承認</button>
                @else
                    {{-- 承認済み(2)の場合：テキスト表示 --}}
                    <div class="attendance-detail__approved-label">承認済み</div>
                @endif
            </div>
        </form>
    </div>
@endsection
