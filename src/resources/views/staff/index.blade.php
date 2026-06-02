@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page__title">スタッフ一覧</h1>
    <table class="table">
        <thead class="table__head">
            <tr class="table__row">
                <th class="table__header">名前</th>
                <th class="table__header">メールアドレス</th>
                <th class="table__header">月次勤怠</th>
            </tr>
        </thead>
        <tbody class="table__body">
            @foreach ($users as $user)
                <tr class="table__row">
                    <td class="table__cell">{{ $user->name }}</td>
                    <td class="table__cell">{{ $user->email }}</td>
                    <td class="table__cell">
                        <a href="{{ route('admin.attendance.staff', ['id' => $user->id]) }}" class="attendance-detail">詳細</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
