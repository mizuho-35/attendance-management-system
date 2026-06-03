<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Work;
use App\Models\BreakTime;
use Carbon\Carbon;

class StampTest extends TestCase
{
    use RefreshDatabase;

    public function test_datetime_and_status_before_work(): void
    {
        $user = User::factory()->create(['role' => 0]);
        $this->actingAs($user);

        // テスト内の時間を固定（2026年6月2日 08:00）
        Carbon::setTestNow(Carbon::create(2026, 6, 2, 8, 0, 0));

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('2026年6月2日(火)');
        $response->assertSee('08:00');
        $response->assertSee('勤務外');
        $response->assertSee('出勤');
    }

    public function test_status_working(): void
    {
        $user = User::factory()->create(['role' => 0]);
        Work::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'work_start' => '09:00:00',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
        $response->assertSee('退勤');
        $response->assertSee('休憩入');
    }

    public function test_status_on_break(): void
    {
        $user = User::factory()->create(['role' => 0]);

        $work = Work::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'work_start' => '09:00:00',
        ]);

        BreakTime::create([
            'work_id' => $work->id,
            'break_start' => '12:00:00',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');
    }

    public function test_status_after_work(): void
    {
        $user = User::factory()->create(['role' => 0]);

        Work::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
        $response->assertDontSee('出勤');
    }

    public function test_attendance_start_function(): void
    {
        $user = User::factory()->create(['role' => 0]);
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 6, 2, 9, 0, 0));

        $response = $this->post('/attendance/start');

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'work_date' => '2026-06-02',
            'work_start' => '2026-06-02 09:00:00',
        ]);
    }

    public function test_break_start_and_end_multiple_times(): void
    {
        $user = User::factory()->create(['role' => 0]);
        $work = Work::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-02',
            'work_start' => '2026-06-02 09:00:00',
        ]);
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 6, 2, 12, 0, 0));
        $this->post('/attendance/break/start');

        $this->assertDatabaseHas('break_times', [
            'work_id' => $work->id,
            'break_start' => '2026-06-02 12:00:00',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 6, 2, 13, 0, 0));
        $this->post('/attendance/break/end');

        $this->assertDatabaseHas('break_times', [
            'work_id' => $work->id,
            'break_start' => '2026-06-02 12:00:00',
            'break_end' => '2026-06-02 13:00:00',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 6, 2, 15, 0, 0));
        $this->post('/attendance/break/start');

        $this->assertEquals(2, BreakTime::where('work_id', $work->id)->count());
    }

    public function test_attendance_end_function(): void
    {
        $user = User::factory()->create(['role' => 0]);
        Work::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-02',
            'work_start' => '2026-06-02 09:00:00',
        ]);

        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 6, 2, 18, 0, 0));
        $this->post('/attendance/end');

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'work_date' => '2026-06-02',
            'work_end' => '2026-06-02 18:00:00',
        ]);
    }
}
