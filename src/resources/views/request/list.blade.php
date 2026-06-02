@extends('layouts.default')

@section('title','申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page__title">申請一覧</h1>
    <div class="request-tabs">
        <a href="{{ route('request.list', ['status' => 0]) }}" class="{{ request('status') == 0 || request('status') === null ? 'active' : '' }}">承認待ち</a>
        <a href="{{ route('request.list', ['status' => 1]) }}" class="{{ request('status') == 1 ? 'active' : '' }}">承認済み</a>
    </div>
    <table class="table">
        <thead class="table__head">
            <tr class="table__row">
                <th class="table__header">状態</th>
                <th class="table__header">名前</th>
                <th class="table__header">対象日付</th>
                <th class="table__header">申請理由</th>
                <th class="table__header">申請日時</th>
                <th class="table__header">詳細</th>
            </tr>
        </thead>

        <tbody class="table__body">
            @foreach ($requests as $req)
                <tr class="table__row">
                    <td class="table__cell">
                        @if ($req->status == 0)
                            承認待ち
                        @elseif ($req->status == 1)
                            承認済み
                        @endif
                    </td>
                    <td class="table__cell">{{ $req->user->name }}</td>
                    <td class="table__cell">{{ $req->work ? \Carbon\Carbon::parse($req->work->work_date)->format('Y/m/d') : '未登録' }}
                    </td>
                    <td class="table__cell">{{ $req->remarks }}</td>
                    <td class="table__cell">{{ $req->created_at->format('Y/m/d') }}</td>
                    <td class="table__cell">
                        @if (auth()->user()->role == 1)
                            <a href="{{ route('admin.request.show', ['attendance_correct_request_id' => $req->id]) }}" class="attendance-detail">
                                詳細
                            </a>
                        @else
                            @if ($req->status == 1)
                                <a href="{{ route('attendance.detail', ['id' => $req->work_id]) }}?request_id={{ $req->id }}" class="attendance-detail">
                                    詳細
                                </a>
                            @else
                                <a href="{{ route('attendance.detail', ['id' => $req->work_id]) }}" class="attendance-detail">
                                    詳細
                                </a>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
