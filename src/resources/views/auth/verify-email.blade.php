<!-- メール認証画面 -->
@extends('layouts.auth')

@section('title','メール認証')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/auth/verify.css') }}">
@endsection

@section('content')
<div class="verify-page">
    <div class="verify__text">
        <p class="verify__text-mail">登録していただいたメールアドレスに認証メールを送付しました。<br />メール認証を完了してください。</p>
    </div>
    <div class="verify-link">
        <a href="http://localhost:8025" class="verify-button">認証はこちらから</a>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="form">
        @csrf
        <button type="submit" class="form__button-link">認証メールを再送する
    </form>
</div>
@endsection