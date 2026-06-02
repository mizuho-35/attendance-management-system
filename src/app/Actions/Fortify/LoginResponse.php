<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->is('admin/login')) {
            return redirect('/admin/attendance/list');
        }

        return redirect('/attendance');
    }
}
