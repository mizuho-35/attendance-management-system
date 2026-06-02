<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;


class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => '西 玲奈',
                'email' => 'reina.n@coachtech.com',
            ],
            [
                'name' => '山田 太郎',
                'email' => 'taro.y@coachtech.com',
            ],
            [
                'name' => '増田 一世',
                'email' => 'issei.m@coachtech.com',
            ],
            [
                'name' => '山本 敬吉',
                'email' => 'keikichi.y@coachtech.com',
            ],
            [
                'name' => '秋田 朋美',
                'email' => 'tomomi.a@coachtech.com',
            ],
            [
                'name' => '中西 教夫',
                'email' => 'norio.n@coachtech.com',
            ],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
            ]);
        }
    }

}
