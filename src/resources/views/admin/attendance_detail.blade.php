@extends('admin.layouts.default')

@section('content')
    <div class="attendance-detail">
        <h1 class="attendance-detail__heading">勤怠詳細</h1>

        <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST" class="attendance-detail__form">
            @csrf
            <table class="attendance-detail__table">
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">名前</th>
                    <td class="attendance-detail__value">
                        <span class="attendance-detail__user-name">{{ $user->name }}</span>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">日付</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__date-container">
                            {{-- 年と月日を分けて配置 --}}
                            <span class="attendance-detail__date-year">
                                {{ $attendance ? $attendance->date->format('Y年') : now()->format('Y年') }}
                            </span>
                            <span class="attendance-detail__date-separator"></span> {{-- 空白を作るための要素 --}}
                            <span class="attendance-detail__date-month">
                                {{ $attendance ? $attendance->date->isoFormat('M月D日') : now()->isoFormat('M月D日') }}
                            </span>
                        </div>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">出勤・退勤</th>
                    <td class="attendance-detail__value">
                        @if ($isPending)
                            {{-- 申請中の時はテキスト表示 --}}
                            <div class="attendance-detail__time-group">
                                <span class="attendance-detail__text-time">
                                    {{ $displayData['check_in'] ? $displayData['check_in']->format('H:i') : '' }}
                                </span>
                                <span class="attendance-detail__separator">〜</span>
                                <span class="attendance-detail__text-time">
                                    {{ $displayData['check_out'] ? $displayData['check_out']->format('H:i') : '' }}
                                </span>
                            </div>
                        @else
                            <div class="attendance-detail__time-group">
                                <input type="time" name="check_in" class="attendance-detail__input"
                                    value="{{ $displayData['check_in'] ? $displayData['check_in']->format('H:i') : '' }}">
                                <span class="attendance-detail__separator">〜</span>
                                <input type="time" name="check_out" class="attendance-detail__input"
                                    value="{{ $displayData['check_out'] ? $displayData['check_out']->format('H:i') : '' }}">
                            </div>
                        @endif
                    </td>
                </tr>
                {{-- 休憩欄：データがある分だけ表示。なければ1つ空欄を表示 --}}
                @php
                    if ($isPending) {
                        // 承認待ちのときは、現在登録（申請）されているデータのみを表示
                        $displayRests = $rests;
                    } else {
                        // 通常時は、既存データに空欄を1つ追加して表示
                        $displayRests = $rests->concat([null]);
                    }
                @endphp

                @foreach ($displayRests as $index => $rest)
                    <tr class="attendance-detail__row">
                        <th class="attendance-detail__label">休憩{{ count($displayRests) > 1 ? $index + 1 : '' }}</th>
                        <td class="attendance-detail__value">
                            @if ($isPending)
                                <div class="attendance-detail__time-group">
                                    <span class="attendance-detail__text-time">
                                        {{ $rest && $rest->break_start ? $rest->break_start->format('H:i') : '' }}
                                    </span>
                                    <span class="attendance-detail__separator">〜</span>
                                    <span class="attendance-detail__text-time">
                                        {{ $rest && $rest->break_end ? $rest->break_end->format('H:i') : '' }}
                                    </span>
                                </div>
                            @else
                                <div class="attendance-detail__time-group">
                                    <input type="time" name="break_start[]" class="attendance-detail__input"
                                        value="{{ $rest && $rest->break_start ? $rest->break_start->format('H:i') : '' }}">
                                    <span class="attendance-detail__separator">〜</span>
                                    <input type="time" name="break_end[]" class="attendance-detail__input"
                                        value="{{ $rest && $rest->break_end ? $rest->break_end->format('H:i') : '' }}">
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach

                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">備考</th>
                    <td class="attendance-detail__value">
                        @if ($isPending)
                            {{-- 申請中の時はテキストのみ表示 --}}
                            <div class="attendance-detail__text-remarks">{!! nl2br(e($displayData['remarks'])) !!}</div>
                        @else
                            <textarea name="remarks" class="attendance-detail__textarea" rows="4">{{ $displayData['remarks'] }}</textarea>
                        @endif
                    </td>
                </tr>
            </table>

            <div class="attendance-detail__actions">
                @if ($isPending)
                    {{-- 承認待ちの場合：メッセージを表示 --}}
                    <p class="attendance-detail__pending-msg">＊承認待ちのため修正はできません。</p>
                @else
                    {{-- 通常時：修正ボタンを表示 --}}
                    <button type="submit" class="attendance-detail__submit-button">修正</button>
                @endif
            </div>

        </form>
    </div>
@endsection
