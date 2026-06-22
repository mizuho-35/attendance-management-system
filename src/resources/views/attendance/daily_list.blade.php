<!-- 日勤怠一覧画面（管理者用） -->
@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/list.css') }}">
@endsection

@section('content')

<div class="container">
    <h1 class="page__title">{{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年M月D日') }}の勤怠一覧</h1>
    <div class="bar">
        <a href="{{ route('admin.attendance.daily_list', ['date' => $previousDate]) }}" class="arrow prev-date">前日</a>
        <form action="" method="GET" id="date-form">
            <div class="date-picker-container" onclick="document.getElementById('date-picker').showPicker()">
                <span class="calendar-icon">📅</span>
                <span id="display-date" class="display-date">
                    {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}
                </span>
                <input type="date" name="date" id="date-picker" value="{{ $date }}" onchange="handleDateChange(this.value)" class="hidden-input">
            </div>
        </form>
        <a href="{{ route('admin.attendance.daily_list', ['date' => $nextDate]) }}" class="arrow next-date">翌日</a>
    </div>

    <table class="table">
        <thead class="table__head">
            <tr class="table__row">
                <th class="table__header">名前</th>
                <th class="table__header">出勤</th>
                <th class="table__header">退勤</th>
                <th class="table__header">休憩</th>
                <th class="table__header">合計</th>
                <th class="table__header">詳細</th>
            </tr>
        </thead>
        <tbody class="table__body">
            @foreach ($users as $user)
                @php
                    $work = $works[$user->id] ?? null;
                @endphp
                <tr class="table__row">
                    <td class="table__cell">{{ $user->name }}</td>
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
                        @if ($work)
                            <a href="{{ route('admin.attendance.detail', [
                                'id' => $work->id,
                                'user_id' => $user->id
                            ]) }}" class="attendance-detail">詳細</a>
                        @else
                            <a href="{{ route('admin.attendance.detail', [
                                'id' => 0,
                                'date' => $date,
                                'user_id' => $user->id
                            ]) }}" class="attendance-detail">詳細</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
function handleDateChange(dateStr) {
    const formatted = dateStr.replace(/-/g, '/');
    document.getElementById('display-date').textContent = formatted;
    window.location.href = "?date=" + dateStr;
}
</script>
@endsection


