<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Work;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTimesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $works = Work::all();

        foreach ($works as $work) {

            $breakStart = Carbon::parse($work->work_date . ' 12:00');
            $breakEnd   = Carbon::parse($work->work_date . ' 13:00');

            BreakTime::create([
                'work_id'     => $work->id,
                'break_start' => $breakStart,
                'break_end'   => $breakEnd,
            ]);
        }
    }
}
