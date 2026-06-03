<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkFactory extends Factory
{
    protected $model = Work::class;

    public function definition(): array
    {
        $workDate = $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d');

        return [
            'user_id' => User::factory(),
            'work_date' => $workDate,
            'work_start' => "{$workDate} 09:00:00",
            'work_end' => "{$workDate} 18:00:00",
            'break_total' => '01:00:00',
            'work_total' => '08:00:00',
        ];
    }
}