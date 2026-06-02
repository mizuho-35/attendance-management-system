<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (Auth::check()) {

            if (Auth::user()->role === 1) {
                return redirect('/admin/attendance/list');
            }

            return redirect('/attendance');
        }

        return $next($request);
    }
}
