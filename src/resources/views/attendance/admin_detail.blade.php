<!-- 勤怠詳細画面（管理者用） -->
@extends('layouts.default')

@section('title','勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/detail.css') }}">
@endsection

@section('content')

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="container">
    <h1 class="page__title">勤怠詳細</h1>
    <form class="form" action="{{ $mode === 'edit' ? route('admin.attendance.update', ['id' => $work->id]) : route('admin.request.approve', ['attendance_correct_request_id' => $latestRequest->id ?? 0]) }}" method="POST">
        @csrf
        @if (($work->id ?? 0) == 0)
            <input type="hidden" name="work_date" value="{{ request('date') }}">
            <input type="hidden" name="user_id" value="{{ request('user_id') }}">
        @endif
        <table class="table">
            <tr class="table__row">
                <th class="table__header">名前</th>
                <td class="table__detail">
                    <div class="table__detail-cell">
                        {{ $user->name }}
                    </div>
                </td>
            </tr>

            <tr class="table__row">
                <th class="table__header">日付</th>
                <td class="table__detail">
                    <div class="table__detail-cell">
                        @php
                            $date = $work->work_date ?? request('date');
                        @endphp
                        <span class="input-request_space">{{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年') }}</span>
                        <span class="input-request_space">{{ \Carbon\Carbon::parse($date)->isoFormat('M月D日') }}</span>
                    </div>
                </td>
            </tr>

            <tr class="table__row">
                <th class="table__header">出勤・退勤</th>
                <td class="table__detail">
                    <div class="table__detail-cell">
                        @if ($mode === 'edit')
                            <input class="form-input time-input" type="time" name="work_start" value="{{ old('work_start', $viewData['work_start']) }}">
                            <p class="input-request_space">〜</p>
                            <input class="form-input time-input" type="time" name="work_end" value="{{ old('work_end', $viewData['work_end']) }}">
                        @else
                            <p>{{ $viewData['work_start'] ?? '' }}</p>
                            <p class="input-request_space">〜</p>
                            <p>{{ $viewData['work_end'] ?? '' }}</p>
                        @endif
                    </div>
                    <div class="form__error">
                        @error('work_start')
                            {{ $message }}
                        @enderror
                        @error('work_end')
                            {{ $message }}
                        @enderror
                    </div>
                </td>
            </tr>

            @foreach ($viewData['breaks'] as $index => $break)
                <tr class="table__row">
                    <th class="table__header">休憩{{ $index === 1 ? '' : $index }}</th>
                    <td class="table__detail">
                        <div class="table__detail-cell">
                            @if ($mode === 'edit')
                                <input class="form-input time-input" type="time" name="breaks[{{ $index }}][start]" value="{{ old("breaks.$index.start", $break['start']) }}">
                                <p class="input-request_space">〜</p>
                                <input class="form-input time-input" type="time" name="breaks[{{ $index }}][end]" value="{{ old("breaks.$index.end", $break['end']) }}">
                            @else
                                <p>{{ $break['start'] ?? '' }}</p>
                                <p class="input-request_space">〜</p>
                                <p>{{ $break['end'] ?? '' }}</p>
                            @endif
                        </div>
                        <div class="form__error">
                            @error("breaks.$index.start")
                                {{ $message }}
                            @enderror
                            @error("breaks.$index.end")
                                {{ $message }}
                            @enderror
                        </div>
                    </td>
                </tr>
            @endforeach
            @if ($mode === 'edit')
                @php
                    $totalBreaks = count($viewData['breaks']);
                    $lastBreak = end($viewData['breaks']);
                    $nextIndex = $totalBreaks + 1;
                    $showNextInput = ($totalBreaks === 0) || (!empty($lastBreak['start']) || !empty($lastBreak['end']));
                @endphp

                @if ($showNextInput)
                <tr class="table__row">
                    <th class="table__header">休憩{{ $nextIndex }}</th>
                    <td class="table__detail">
                        <div class="table__detail-cell">
                            <input class="form-input time-input" type="time" name="breaks[{{ $nextIndex }}][start]" value="{{ old("breaks.$nextIndex.start") }}">
                            <p class="input-request_space">〜</p>
                            <input class="form-input time-input" type="time" name="breaks[{{ $nextIndex }}][end]" value="{{ old("breaks.$nextIndex.end") }}">
                        </div>
                        <div class="form__error">
                            @error("breaks.$nextIndex.start")
                                {{ $message }}
                            @enderror
                            @error("breaks.$nextIndex.end")
                                {{ $message }}
                            @enderror
                        </div>
                    </td>
                </tr>
                @endif
            @endif

            <tr class="table__row">
                <th class="table__header">備考</th>
                <td class="table__detail">
                    <div class="table__detail-cell">
                        @if ($mode === 'edit')
                            <textarea class="form-input input-request" name="remarks" rows="3">{{ old('remarks', $viewData['remarks'] ?? '') }}</textarea>
                        @else
                            <p>{{ $viewData['remarks'] ?? '-' }}</p>
                        @endif
                    </div>
                    <div class="form__error">
                        @error('remarks')
                            {{ $message }}
                        @enderror
                    </div>
                </td>
            </tr>
        </table>

        @if ($mode === 'edit')
            <button class="btn--small">修正</button>
        @elseif ($mode === 'pending')
            <button class="btn--small">承認する</button>
        @elseif ($mode === 'approved_log')
            <button class="approved-btn" disabled>承認済み</button>
        @endif
    </form>
</div>
@endsection