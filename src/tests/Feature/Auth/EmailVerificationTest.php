<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use App\Models\User;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_is_redirected_to_verification_notice_page()
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => null,
        ]);
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertRedirect('/email/verify');
    }

    public function test_verification_notice_page_can_be_rendered()
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('認証');
    }

    public function test_user_can_verify_email_and_redirect_to_attendance()
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);
        $response->assertRedirect('/login');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
