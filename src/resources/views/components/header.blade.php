<header class="header">
    <div class="header__logo">
        @php
            $route = Route::currentRouteName();
        @endphp

        @if (auth()->check())
            @if (auth()->user()->role === 1)
                <a href="/admin/attendance/list">
                    <img src="{{ asset('img/logo.png') }}" alt="ロゴ">
                </a>
            @else
                <a href="/attendance">
                    <img src="{{ asset('img/logo.png') }}" alt="ロゴ">
                </a>
            @endif

        @else
            @if ($route === 'admin.login')
                <a href="/admin/login">
                    <img src="{{ asset('img/logo.png') }}" alt="ロゴ">
                </a>
            @else
                <a href="/login">
                    <img src="{{ asset('img/logo.png') }}" alt="ロゴ">
                </a>
            @endif
        @endif
    </div>

    @if( !in_array(Route::currentRouteName(), ['register', 'login', 'admin.login', 'verification.notice']) )
    <nav class="header__nav">
        <ul>
            @if (auth()->check() && auth()->user()->role === 1)
            <li><a href="/admin/attendance/list">勤怠一覧</a></li>
            <li><a href="/admin/staff/list">スタッフ一覧</a></li>
            <li><a href="/stamp_correction_request/list">申請一覧</a></li>
            <li>
                <form action="/admin/logout" method="post">
                    @csrf
                    <button class="header__logout">ログアウト</button>
                </form>
            </li>

            @elseif (auth()->check() && auth()->user()->role === 0)
            <li><a href="/attendance">勤怠</a></li>
            <li><a href="/attendance/list">勤怠一覧</a></li>
            <li><a href="/stamp_correction_request/list">申請</a></li>
            <li>
                <form action="/logout" method="post">
                    @csrf
                    <button class="header__logout">ログアウト</button>
                </form>
            </li>
            @endif
        </ul>
    </nav>
    @endif
</header>