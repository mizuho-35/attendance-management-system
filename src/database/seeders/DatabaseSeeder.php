<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Work;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'email_verified_at' => now(),
        ]);
        User::create([
            'name' => 'スタッフ',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $this->call([
            UsersTableSeeder::class,
            WorksTableSeeder::class,
            BreakTimesTableSeeder::class,
        ]);
    }
}
