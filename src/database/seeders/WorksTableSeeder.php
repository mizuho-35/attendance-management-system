<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;

class WorksTableSeeder extends Seeder
{
    public function run()
    {
        $staffUsers = User::where('role', 0)->get();

        foreach ($staffUsers as $user) {

            for ($dayOffset = 1; $dayOffset <= 5; $dayOffset++) {

                $date = Carbon::today()->subDays($dayOffset)->toDateString();

                Work::factory()->create([
                    'user_id'     => $user->id,
                    'work_date'   => $date,
                    'work_start'  => "{$date} 09:00:00",
                    'work_end'    => "{$date} 18:00:00",
                    'break_total' => '01:00:00',
                    'work_total'  => '08:00:00',
                ]);
            }
        }
    }
}

