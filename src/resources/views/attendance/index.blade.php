<!-- 勤怠登録画面（スタッフ用） -->
@extends('layouts.default')

@section('title','勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance/index.css') }}">
@endsection

@section('content')

@if ($status === 'before_work')
<div class="attendance">
    <p class="status">勤務外</p>
    <h1 class="date">{{ now()->isoFormat('YYYY年M月D日(ddd)')}}</h1>
    <h2 class="datetime">{{ now()->format('H:i') }}</h2>
    <div class="btn-row">
        <form action="/attendance/start" method="post" class="form">
            @csrf
            <button class="work__btn">出勤</button>
        </form>
    </div>
</div>

@elseif ($status === 'working')
<div class="attendance">
    <p class="status">出勤中</p>
    <h1 class="date">{{ now()->isoFormat('YYYY年M月D日(ddd)')}}</h1>
    <h2 class="datetime">{{ now()->format('H:i') }}</h2>
    <div class="btn-row">
        <form action="/attendance/end" method="post" class="form">
            @csrf
            <button class="work__btn">退勤</button>
        </form>
        <form action="/attendance/break/start" method="post" class="form">
            @csrf
            <button class="break__btn">休憩入</button>
        </form>
    </div>
</div>

@elseif ($status === 'on_break')
<div class="attendance">
    <p class="status">休憩中</p>
    <h1 class="date">{{ now()->isoFormat('YYYY年M月D日(ddd)')}}</h1>
    <h2 class="datetime">{{ now()->format('H:i') }}</h2>
    <div class="btn-row">
        <form action="/attendance/break/end" method="post" class="form">
            @csrf
            <button class="break__btn">休憩戻</button>
        </form>
    </div>
</div>

@elseif ($status === 'after_work')
<div class="attendance">
    <p class="status">退勤済</p>
    <h1 class="date">{{ now()->isoFormat('YYYY年M月D日(ddd)')}}</h1>
    <h2 class="datetime">{{ now()->format('H:i') }}</h2>
    <p class="message">お疲れ様でした。</p>
</div>
@endif
@endsection