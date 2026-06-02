<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;

class WorksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('role', 0)->get();
        $dates = [
            '2026-04-01',
            '2026-04-02',
            '2026-04-03',
            '2026-05-01',
        ];

        foreach ($users as $user) {
            foreach ($dates as $date) {

                $breakMinutes = 60;
                $breakH = floor($breakMinutes / 60);
                $breakM = $breakMinutes % 60;

                $workMinutes = 540;
                $workH = floor($workMinutes / 60);
                $workM = $workMinutes % 60;

                Work::create([
                    'user_id'     => $user->id,
                    'work_date'   => $date,
                    'work_start'  => Carbon::parse($date . ' 08:00'),
                    'work_end'    => Carbon::parse($date . ' 18:00'),
                    'break_total' => sprintf('%02d:%02d:00', $breakH, $breakM),
                    'work_total'  => sprintf('%02d:%02d:00', $workH, $workM),
                ]);
            }
        }
    }
}
