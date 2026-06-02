<?php

namespace App\Providers;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Actions\Fortify\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Actions\Fortify\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Actions\Fortify\RegisterResponse;
use Illuminate\Validation\ValidationException;


class FortifyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->bind(\Laravel\Fortify\Http\Requests\LoginRequest::class, function ($app) {
            if (request()->is('admin/login')) {
                return new \App\Http\Requests\AdminLoginRequest();
            }
            return new \App\Http\Requests\LoginRequest();
        });

    }

    public function boot()
    {
        Fortify::loginView(function () {
            if (request()->is('admin/*')) {
                return view('auth.admin_login');
            }
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::authenticateUsing(function ($request) {
            if ($request->is('admin/login')) {
                $user = User::where('email', $request->email)
                    ->where('role', 1)
                    ->first();
                if ($user && Hash::check($request->password, $user->password)) {
                    return $user;
                }
                throw ValidationException::withMessages([
                    'email' => ['ログイン情報が登録されていません。'],
                ]);
            }

            $user = User::where('email', $request->email)
                ->where('role', 0)
                ->first();
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が登録されていません。'],
            ]);
        });
    }
}