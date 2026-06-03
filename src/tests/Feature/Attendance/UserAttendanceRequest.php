<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Work;

class UserAttendanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_modification_request_submits_successfully(): void
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);
        $work = Work::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-01',
            'work_start' => '2026-06-01 09:00:00',
            'work_end' => '2026-06-01 18:00:00',
        ]);

        $this->actingAs($user);
        $response = $this->post("/attendance/request/{$work->id}", [
            'work_start' => '09:30',
            'work_end' => '18:30',
            'remarks' => '電車の遅延のため修正をお願いします',
        ]);

        $response->assertRedirect("/attendance/detail/{$work->id}");

        $this->assertDatabaseHas('requests', [
            'user_id' => $user->id,
            'work_id' => $work->id,
            'remarks' => '電車の遅延のため修正をお願いします',
            'status' => 0,
        ]);
    }

    public function test_user_can_view_all_requests_and_navigate_to_detail(): void
    {
        $user = User::factory()->create(['role' => 0, 'email_verified_at' => now()]);
        $this->actingAs($user);

        $work1 = Work::create(['user_id' => $user->id, 'work_date' => '2026-06-01']);
        $work2 = Work::create(['user_id' => $user->id, 'work_date' => '2026-06-02']);

        \DB::table('requests')->insert([
            ['user_id' => $user->id, 'work_id' => $work1->id, 'remarks' => '承認待ちの申請その1', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'work_id' => $work2->id, 'remarks' => '承認待ちの申請その2', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'work_id' => $work1->id, 'remarks' => '管理者から承認された申請', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認待ちの申請その1');
        $response->assertSee('承認待ちの申請その2');
        $response->assertSee('管理者から承認された申請');

        $response->assertSee('詳細');
    }
}