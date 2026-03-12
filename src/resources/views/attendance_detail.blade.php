@extends('layouts.default')

@section('content')
    <div class="attendance-detail">
        <h2 class="attendance-detail__heading">勤怠詳細</h2>

        <form action="{{ route('attendance.update', $date) }}" method="POST" class="attendance-detail__form">
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
                        <span class="attendance-detail__date-text">{{ $formattedDate }}</span>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">出勤・退勤</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__time-group">

                            <input type="time" name="check_in" class="attendance-detail__input"
                                value="{{ $attendance && $attendance->check_in ? $attendance->check_in->format('H:i') : '' }}">
                            <span class="attendance-detail__separator">〜</span>
                            <input type="time" name="check_out" class="attendance-detail__input"
                                value="{{ $attendance && $attendance->check_out ? $attendance->check_out->format('H:i') : '' }}">
                        </div>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">休憩</th>
                    <td class="attendance-detail__value">
                        <div class="attendance-detail__time-group">
                            <input type="time" name="break_start" class="attendance-detail__input"
                                value="{{ $attendance && $attendance->rests->first() ? $attendance->rests->first()->break_start->format('H:i') : '' }}">
                            <span class="attendance-detail__separator">〜</span>
                            <input type="time" name="break_end" class="attendance-detail__input"
                                value="{{ $attendance && $attendance->rests->first() ? $attendance->rests->first()->break_end->format('H:i') : '' }}">
                        </div>
                    </td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail__label">備考</th>
                    <td class="attendance-detail__value">
                        <textarea name="remarks" class="attendance-detail__textarea" rows="4">{{ $attendance ? $attendance->remarks : '' }}</textarea>
                    </td>
                </tr>
            </table>

            <div class="attendance-detail__actions">
                <button type="submit" class="attendance-detail__submit-btn">修正</button>
            </div>
        </form>
    </div>
@endsection
