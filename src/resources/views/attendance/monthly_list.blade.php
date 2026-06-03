@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/list.css') }}">
@endsection

@section('content')
<div class="container">
    @if (session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif
    <h1 class="page__title">
        @isset($user)
            {{ $user->name }}さんの勤怠
        @else
            勤怠一覧
        @endisset
    </h1>
    <div class="bar">
        <a href="{{ isset($user) ? route('admin.attendance.staff', ['id' => $user->id, 'month' => $previousMonth]) : route('attendance.monthly_list', ['month' => $previousMonth]) }}" class="arrow prev-date">前月</a>

        <form action="" method="GET" id="month-form">
            <div class="date-picker-container" onclick="document.getElementById('month-picker').showPicker()">
                <span class="calendar-icon">📅</span>
                <span id="display-date" class="display-date">
                    {{ \Carbon\Carbon::parse($currentMonth)->format('Y/m') }}
                </span>
                <input type="date" name="date" id="month-picker" value="{{ \Carbon\Carbon::parse($currentMonth)->format('Y-m-01') }}" onchange="handleDateChange(this.value)" class="hidden-input">
            </div>
        </form>

        <a href="{{ isset($user) ? route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) : route('attendance.monthly_list', ['month' => $nextMonth]) }}" class="arrow next-date">翌月</a>
    </div>

    <table class="table">
        <thead class="table__head">
            <tr class="table__row">
                <th class="table__header">日付</th>
                <th class="table__header">出勤</th>
                <th class="table__header">退勤</th>
                <th class="table__header">休憩</th>
                <th class="table__header">合計</th>
                <th class="table__header">詳細</th>
            </tr>
        </thead>
        <tbody class="table__body">
            @foreach ($dates as $date)
                @php
                    $dateStr = $date->format('Y-m-d');
                    $work = $works[$dateStr] ?? null;
                @endphp
                <tr class="table__row">
                    <td class="table__cell">{{ $date->isoFormat('MM/DD(ddd)') }}</td>
                    <td class="table__cell">
                        {{ $work && $work->work_start ? $work->work_start->format('H:i') : '' }}
                    </td>
                    <td class="table__cell">
                        {{ $work && $work->work_end ? $work->work_end->format('H:i') : '' }}
                    </td>
                    <td class="table__cell">
                        {{ $work && $work->break_total ? substr($work->break_total, 0, 5) : '' }}
                    </td>
                    <td class="table__cell">
                        {{ $work && $work->work_total ? substr($work->work_total, 0, 5) : '' }}
                    </td>
                    <td class="table__cell">
                        @isset($user)
                            <a href="{{ route('admin.attendance.detail', [ 'id' => $work->id ?? 0, 'date' => $dateStr, 'user_id' => $user->id ]) }}" class="attendance-detail">詳細</a>
                        @else
                            <a href="{{ route('attendance.detail', [ 'id' => $work->id ?? 0, 'date' => $dateStr ]) }}" class="attendance-detail">詳細</a>
                        @endisset
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @isset($user)
    <div class="csv-export">
        <form action="{{ route('admin.attendance.export') }}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $user->id }}">
            <input type="hidden" name="month" value="{{ $currentMonth }}">
            <button type="submit" class="btn--small">CSV出力</button>
        </form>
    </div>
    @endisset
</div>
@endsection

@section('scripts')
<script>
function handleDateChange(dateStr) {
    const month = dateStr.slice(0, 7);
    document.getElementById('display-date').textContent = month.replace('-', '/');
    window.location.href = "?month=" + month;
}
</script>
@endsection
