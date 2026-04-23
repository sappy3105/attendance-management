@extends('admin.layouts.default')

@section('content')
    <div class="staff-list">
        <h1 class="staff-list__heading">スタッフ一覧</h1>

        <table class="staff-list__table">
            <thead class="staff-list__thead">
                <tr class="staff-list__row">
                    <th class="staff-list__label">名前</th>
                    <th class="staff-list__label">メールアドレス</th>
                    <th class="staff-list__label">月次勤怠</th>
                </tr>
            </thead>
            <tbody class="staff-list__tbody">
                @foreach ($users as $user)
                    <tr class="staff-list__row">
                        <td class="staff-list__value">{{ $user->name }}</td>
                        <td class="staff-list__value">{{ $user->email }}</td>
                        <td class="staff-list__value">
                            <a href="{{ route('admin.staff.attendance', ['id' => $user->id]) }}"
                                class="staff-list__link">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
