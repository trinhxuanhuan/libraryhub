<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\PasswordChangeOtpMail;
use App\Models\User;
use App\Services\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_guest_cannot_request_or_confirm_password_change(): void
    {
        $this->patch(route('profile.password.request'), [])
            ->assertRedirect(route('login'));

        $this->post(route('profile.password.confirm'), [
            'otp' => '123456',
        ])->assertRedirect(route('login'));
    }

    public function test_wrong_current_password_does_not_send_otp(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange('WrongPassword123')
            ->assertSessionHasErrors('current_password');

        Mail::assertNothingSent();

        $this->assertDatabaseMissing('password_change_requests', [
            'user_id' => $user->id,
        ]);
    }

    public function test_password_is_not_changed_before_otp_confirmation(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange()
            ->assertRedirect(route('profile.password.verify'));

        Mail::assertSent(PasswordChangeOtpMail::class, 1);

        $this->assertDatabaseHas('password_change_requests', [
            'user_id' => $user->id,
            'attempts' => 0,
        ]);

        $hasher = app(PasswordHasher::class);

        $this->assertTrue(
            $hasher->verify(
                'CurrentPassword123',
                $user->fresh()->password
            )
        );

        $this->assertFalse(
            $hasher->verify(
                'NewPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_valid_otp_changes_password_only_once(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange()
            ->assertRedirect(route('profile.password.verify'));

        $otp = $this->sentOtp();

        $this->post(route('profile.password.confirm'), [
            'otp' => $otp,
        ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('success');

        $hasher = app(PasswordHasher::class);
        $user->refresh();

        $this->assertTrue(
            $hasher->verify('NewPassword123', $user->password)
        );

        $this->assertFalse(
            $hasher->verify('CurrentPassword123', $user->password)
        );

        $this->assertDatabaseMissing('password_change_requests', [
            'user_id' => $user->id,
        ]);

        $this->post(route('profile.password.confirm'), [
            'otp' => $otp,
        ])->assertSessionHasErrors('otp');

        $this->get(route('profile.show'))->assertOk();
    }

    public function test_wrong_otp_increases_attempt_count(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange();
        $wrongOtp = $this->wrongOtp();

        $this->post(route('profile.password.confirm'), [
            'otp' => $wrongOtp,
        ])->assertSessionHasErrors('otp');

        $this->assertDatabaseHas('password_change_requests', [
            'user_id' => $user->id,
            'attempts' => 1,
        ]);

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'CurrentPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_five_wrong_attempts_invalidate_the_request(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange();
        $wrongOtp = $this->wrongOtp();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('profile.password.confirm'), [
                'otp' => $wrongOtp,
            ])->assertSessionHasErrors('otp');
        }

        $this->assertDatabaseMissing('password_change_requests', [
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'CurrentPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_expired_otp_cannot_change_password(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange();
        $otp = $this->sentOtp();

        $this->travel(11)->minutes();

        $this->post(route('profile.password.confirm'), [
            'otp' => $otp,
        ])->assertSessionHasErrors('otp');

        $this->assertDatabaseMissing('password_change_requests', [
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'CurrentPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_immediate_resend_is_rejected(): void
    {
        $this->loginUser();

        $this->requestPasswordChange();

        $this->post(route('profile.password.resend'))
            ->assertSessionHasErrors('otp');

        Mail::assertSent(PasswordChangeOtpMail::class, 1);
    }

    public function test_previous_request_becomes_invalid_if_password_changes(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange();
        $otp = $this->sentOtp();

        $user->forceFill([
            'password' => app(PasswordHasher::class)
                ->hash('AnotherPassword123'),
        ])->save();

        $this->post(route('profile.password.confirm'), [
            'otp' => $otp,
        ])->assertSessionHasErrors('otp');

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'AnotherPassword123',
                $user->fresh()->password
            )
        );

        $this->assertDatabaseMissing('password_change_requests', [
            'user_id' => $user->id,
        ]);
    }

    private function loginUser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Member,
            'is_active' => true,
            'password' => app(PasswordHasher::class)
                ->hash('CurrentPassword123'),
        ]);

        $this->withSession([
            'manual_auth_user_id' => $user->id,
        ]);

        return $user;
    }

    private function requestPasswordChange(
        string $currentPassword = 'CurrentPassword123'
    ) {
        return $this->patch(route('profile.password.request'), [
            'current_password' => $currentPassword,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);
    }

    private function sentOtp(): string
    {
        return Mail::sent(PasswordChangeOtpMail::class)
            ->first()
            ->code;
    }

    private function wrongOtp(): string
    {
        return $this->sentOtp() === '000000'
            ? '999999'
            : '000000';
    }

    public function test_resend_after_cooldown_sends_new_otp(): void
    {
        $user = $this->loginUser();

        $this->requestPasswordChange()
            ->assertRedirect(route('profile.password.verify'));

        Mail::assertSent(PasswordChangeOtpMail::class, 1);

        $this->travel(61)->seconds();

        $this->post(route('profile.password.resend'))
            ->assertRedirect(route('profile.password.verify'))
            ->assertSessionHas('success');

        Mail::assertSent(PasswordChangeOtpMail::class, 2);

        $this->assertDatabaseHas('password_change_requests', [
            'user_id' => $user->id,
            'attempts' => 0,
        ]);

        $latestOtp = Mail::sent(PasswordChangeOtpMail::class)
            ->last()
            ->code;

        $this->post(route('profile.password.confirm'), [
            'otp' => $latestOtp,
        ])->assertRedirect(route('profile.show'));

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'NewPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_mail_failure_does_not_leave_pending_request(): void
    {
        $user = $this->loginUser();

        Mail::shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andThrow(new \RuntimeException('SMTP unavailable'));

        $this->requestPasswordChange()
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseMissing('password_change_requests', [
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'CurrentPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_sending_more_than_five_otp_emails_per_hour_is_blocked(): void
    {
        $user = $this->loginUser();

        for ($i = 0; $i < 5; $i++) {
            $this->requestPasswordChange()
                ->assertRedirect(route('profile.password.verify'));

            $this->travel(61)->seconds();
        }

        $this->requestPasswordChange()
            ->assertSessionHasErrors('otp');

        Mail::assertSent(PasswordChangeOtpMail::class, 5);

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'CurrentPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_password_change_is_rejected_when_user_lock_is_held(): void
    {
        $user = $this->loginUser();

        $lock = Cache::lock("password-change:{$user->id}", 120);

        $this->assertTrue($lock->get());

        try {
            $this->requestPasswordChange()
                ->assertSessionHasErrors('otp');

            Mail::assertNothingSent();

            $this->assertDatabaseMissing('password_change_requests', [
                'user_id' => $user->id,
            ]);
        } finally {
            $lock->release();
        }
    }
}
