<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Work;

class UserAttendanceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_monthly_attendance_and_navigate(): void
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);

        Work::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-01',
            'work_start' => '2026-06-01 09:00:00',
            'work_end' => '2026-06-01 18:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?month=2026-06');
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('前月');
        $response->assertSee('翌月');
        $response->assertSee('詳細');

        $responsePrevious = $this->get('/attendance/list?month=2026-05');
        $responsePrevious->assertStatus(200);
        $responsePrevious->assertDontSee('09:00');

        $responseNext = $this->get('/attendance/list?month=2026-07');
        $responseNext->assertStatus(200);
        $responseNext->assertDontSee('09:00');
    }

    public function test_user_can_view_attendance_detail(): void
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);

        $work = Work::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-01',
            'work_start' => '2026-06-01 09:15:00',
            'work_end' => '2026-06-01 18:30:00',
        ]);

        $restTable = \DB::getSchemaBuilder()->hasTable('rests') ? 'rests' : (\DB::getSchemaBuilder()->hasTable('breaks') ? 'breaks' : null);
        if ($restTable) {
            \DB::table($restTable)->insert([
                'work_id' => $work->id,
                'break_start' => '2026-06-01 12:00:00',
                'break_end' => '2026-06-01 13:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$work->id}");
        $response->assertStatus(200);

        $response->assertSee($user->name);
        $response->assertSee('2026-06-01');
        $response->assertSee('09:15');
        $response->assertSee('18:30');
        if ($restTable) {
            $response->assertSee('12:00');
            $response->assertSee('13:00');
        }
    }
}