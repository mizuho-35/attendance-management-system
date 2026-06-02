<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Session;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $role = Session::pull('logout_role');
        if ($role === 1) {
            return redirect('/admin/login');
        }
        return redirect('/login');
    }
}
