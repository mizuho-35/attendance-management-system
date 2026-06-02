<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 1,
            'email_verified_at' => now(),
        ]);

        $this->call([
            UsersTableSeeder::class,
            WorksTableSeeder::class,
            BreakTimesTableSeeder::class,
        ]);
    }
}
