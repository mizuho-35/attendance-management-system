<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminStaffController extends Controller
{
    private const ROLE_STAFF = 0;

    public function index()
    {
        $users = User::where('role', self::ROLE_STAFF)->get();

        return view('staff.index', [
            'users' => $users
        ]);
    }
}
