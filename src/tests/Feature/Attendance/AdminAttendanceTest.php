<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AttendanceRequest;
use App\Models\User;
use App\Models\Work;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_attendance_request_approval_page()
    {
        $user = User::factory()->create(['role' => 0]);
        $admin = User::factory()->create(['role' => 1]);

        $work = new \App\Models\Work();
        $work->user_id = $user->id;
        $work->work_date = '2026-06-03';
        $work->save();

        $attendanceRequest = AttendanceRequest::create([
            'user_id' => $user->id,
            'work_id' => $work->id,
            'status'  => 0,
            'remarks' => '修正をお願いします。',
        ]);

        $response = $this->actingAs($admin)->get("/stamp_correction_request/approve/{$attendanceRequest->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_approve_attendance_modification_request()
    {
        $user = User::factory()->create(['role' => 0]);
        $admin = User::factory()->create(['role' => 1]);

        $work = new \App\Models\Work();
        $work->user_id = $user->id;
        $work->work_date = '2026-06-03';
        $work->save();

        $attendanceRequest = AttendanceRequest::create([
            'user_id' => $user->id,
            'work_id' => $work->id,
            'status'  => 0,
            'remarks' => '修正をお願いします。',
        ]);

        $response = $this->actingAs($admin)->post("/stamp_correction_request/approve/{$attendanceRequest->id}", [
            'status' => 1,
        ]);

        $response->assertRedirect("/stamp_correction_request/approve/{$attendanceRequest->id}");

        $this->assertDatabaseHas('requests', [
            'id'     => $attendanceRequest->id,
            'status' => 1,
        ]);
    }

    public function test_admin_can_view_staff_list()
    {
        $admin = User::factory()->create(['role' => 1]);
        $response = $this->actingAs($admin)->get('/admin/staff/list');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_staff_monthly_attendance()
    {
        $admin = User::factory()->create(['role' => 1]);
        $staff = User::factory()->create(['role' => 0]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$staff->id}");

        $response->assertStatus(200);
    }
}
