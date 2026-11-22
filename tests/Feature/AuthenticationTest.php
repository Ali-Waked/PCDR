<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_rendered_with_arabic_rtl_defaults(): void
    {
        $this->get('/ar/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('locale', 'ar')
                ->where('direction', 'rtl')
                ->where('translations.ui.auth.login_title', 'تسجيل الدخول إلى المنصة'));
    }

    public function test_active_verified_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.test',
            'password' => 'correct-password-123',
        ]);

        $this->post('/ar/login', [
            'email' => $user->email,
            'password' => 'correct-password-123',
        ])->assertRedirect('/ar/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'actor_id' => $user->id,
            'subject_id' => (string) $user->id,
        ]);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/ar/logout')->assertRedirect('/ar/login');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'actor_id' => $user->id,
        ]);
    }

    public function test_invalid_credentials_are_reported_and_audited(): void
    {
        $user = User::factory()->create(['email' => 'invalid@example.test']);

        $this->from('/ar/login')->post('/ar/login', [
            'email' => $user->email,
            'password' => 'wrong-password-123',
        ])->assertRedirect('/ar/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, $user->fresh()->failed_login_attempts);
        $this->assertSame('auth.login_failed', AuditLog::latest()->first()->action);
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->suspended()->create([
            'email' => 'suspended@example.test',
            'password' => 'correct-password-123',
        ]);

        $this->from('/ar/login')->post('/ar/login', [
            'email' => $user->email,
            'password' => 'correct-password-123',
        ])->assertRedirect('/ar/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_temporarily_locked_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@example.test',
            'password' => 'correct-password-123',
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->from('/ar/login')->post('/ar/login', [
            'email' => $user->email,
            'password' => 'correct-password-123',
        ])->assertRedirect('/ar/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertFalse($user->fresh()->isActive());
    }

    public function test_unverified_user_is_sent_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.test',
            'password' => 'correct-password-123',
        ]);

        $this->post('/ar/login', [
            'email' => $user->email,
            'password' => 'correct-password-123',
        ])->assertRedirect('/ar/verify-email');

        $this->assertGuest();
    }

    public function test_email_verification_marks_the_user_verified_and_is_audited(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'locale' => 'ar',
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/ar/dashboard');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.email_verified',
            'actor_id' => $user->id,
        ]);
    }

    public function test_password_reset_link_is_notified_and_token_can_reset_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.test']);

        $this->from('/ar/forgot-password')->post('/ar/forgot-password', [
            'email' => $user->email,
        ])->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $token = Password::broker()->createToken($user);

        $this->post('/ar/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password-123',
            'password_confirmation' => 'brand-new-password-123',
        ])->assertRedirect('/ar/login');

        $this->assertTrue(Hash::check('brand-new-password-123', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset_completed',
            'actor_id' => null,
            'subject_id' => (string) $user->id,
        ]);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create(['email' => 'throttled@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/ar/login', [
                'email' => $user->email,
                'password' => 'wrong-password-123',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/ar/login', [
            'email' => $user->email,
            'password' => $user->password,
        ])->assertStatus(429);
    }
}
