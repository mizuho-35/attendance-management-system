<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;


class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // 1000人分のデータで負荷テストをする場合はcountを1000に書き換える
        User::factory()->count(10)->create([
            'role' => 0,
        ]);
    }
}
