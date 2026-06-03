<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Work;

class UserValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_modification_validation_start_after_end()
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);
        $work = Work::create(['user_id' => $user->id, 'work_date' => '2026-06-01']);

        $response = $this->actingAs($user)->post("/attendance/request/{$work->id}", [
            'work_start' => '19:00',
            'work_end'   => '18:00',
            'remarks'    => '出勤時間を遅く間違えました',
        ]);

        $response->assertSessionHasErrors(['work_start' => '出勤時間が不適切な値です']);
    }

    public function test_attendance_modification_validation_break_start_after_work_end()
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);
        $work = Work::create(['user_id' => $user->id, 'work_date' => '2026-06-01']);

        $response = $this->actingAs($user)->post("/attendance/request/{$work->id}", [
            'work_start'   => '09:00',
            'work_end'     => '18:00',
            'breaks' => [['start' => '19:00', 'end' => '19:30']],
            'remarks'      => '休憩時間を間違えました',
        ]);

        $response->assertSessionHasErrors(['breaks.0.start' => '休憩時間が不適切な値です']);
    }

    public function test_attendance_modification_validation_break_end_after_work_end()
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);
        $work = Work::create(['user_id' => $user->id, 'work_date' => '2026-06-01']);

        $response = $this->actingAs($user)->post("/attendance/request/{$work->id}", [
            'work_start'   => '09:00',
            'work_end'     => '18:00',
            'breaks' => [['start' => '12:00', 'end' => '19:00']],
            'remarks'      => '休憩終了を間違えました',
        ]);

        $response->assertSessionHasErrors(['breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です']);
    }


    public function test_attendance_modification_validation_remarks_required()
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);
        $work = Work::create(['user_id' => $user->id, 'work_date' => '2026-06-01']);

        $response = $this->actingAs($user)->post("/attendance/request/{$work->id}", [
            'work_start'   => '09:00',
            'work_end'     => '18:00',
            'remarks'      => '',
        ]);

        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }
}