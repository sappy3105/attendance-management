@extends('layouts.default')

@section('content')
    <div class="request-list">
        <h1 class="request-list__heading">申請一覧</h1>

        {{-- タブ切り替え部分 --}}
        <div class="request-list__tabs">
            <a href="{{ route('attendance.requests', ['status' => 'pending']) }}"
                class="request-list__tab {{ $status === 'pending' ? 'is-active' : '' }}">承認待ち</a>
            <a href="{{ route('attendance.requests', ['status' => 'approved']) }}"
                class="request-list__tab {{ $status === 'approved' ? 'is-active' : '' }}">承認済み</a>
        </div>

        <table class="request-list__table">
            <thead class="request-list__thead">
                <tr class="request-list__row">
                    <th class="request-list__label">状態</th>
                    <th class="request-list__label">名前</th>
                    <th class="request-list__label">対象日時</th>
                    <th class="request-list__label">申請理由</th>
                    <th class="request-list__label">申請日時</th>
                    <th class="request-list__label">詳細</th>
                </tr>
            </thead>
            <tbody class="request-list__tbody">
                @foreach ($requests as $request)
                    <tr class="request-list__row">
                        <td class="request-list__value">
                            {{ $request->status === 1 ? '承認待ち' : '承認済み' }}
                        </td>
                        <td class="request-list__value">{{ $user->name }}</td>
                        <td class="request-list__value">
                            {{ $request->date->format('Y/m/d') }}
                        </td>
                        <td class="request-list__value">{{ $request->remarks }}</td>
                        <td class="request-list__value">
                            {{ $request->created_at->format('Y/m/d') }}
                        </td>
                        <td class="request-list__value">
                            <a href="{{ route('attendance.detail', ['date' => $request->date->format('Y-m-d')]) }}"
                                class="request-list__link">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
