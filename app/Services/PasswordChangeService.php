<?php

namespace App\Services;

use App\Mail\PasswordChangeOtpMail;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordChangeService
{
    private const OTP_LIFETIME_MINUTES = 10;

    private const MAX_OTP_ATTEMPTS = 5;

    private const MAX_EMAILS_PER_HOUR = 5;

    private const SEND_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly PasswordHasher $passwordHasher
    ) {}

    public function start(
        User $user,
        string $currentPassword,
        string $newPassword
    ): void {
        $this->withUserLock($user, function () use (
            $user,
            $currentPassword,
            $newPassword
        ): void {
            $user->refresh();

            if (! $this->passwordHasher->verify(
                $currentPassword,
                $user->password
            )) {
                throw ValidationException::withMessages([
                    'current_password' => __('message.profile.invalid_current_password'),
                ]);
            }

            $this->sendCode(
                $user,
                $this->passwordHasher->hash($newPassword),
                $this->fingerprint($user->password)
            );
        });
    }

    public function resend(User $user): void
    {
        $this->withUserLock($user, function () use ($user): void {
            $user->refresh();

            $pending = DB::table('password_change_requests')
                ->where('user_id', $user->id)
                ->first();

            if (
                $pending === null
                || Carbon::parse($pending->expires_at)->isPast()
                || ! hash_equals(
                    $pending->password_snapshot,
                    $this->fingerprint($user->password)
                )
            ) {
                throw ValidationException::withMessages([
                    'otp' => __('message.profile.otp_request_expired'),
                ]);
            }

            $this->sendCode(
                $user,
                $pending->pending_password_hash,
                $pending->password_snapshot
            );
        });
    }

    public function confirm(User $user, string $otp): void
    {
        $this->withUserLock(
            $user,
            fn () => $this->confirmLocked($user, $otp)
        );
    }

    private function confirmLocked(User $user, string $otp): void
    {
        $status = DB::transaction(function () use ($user, $otp): string {
            $query = DB::table('password_change_requests')
                ->where('user_id', $user->id);

            $pending = $query->lockForUpdate()->first();

            if ($pending === null) {
                return 'expired';
            }

            if (Carbon::parse($pending->expires_at)->isPast()) {
                $query->delete();

                return 'expired';
            }

            $currentUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            if (
                $currentUser === null
                || ! hash_equals(
                    $pending->password_snapshot,
                    $this->fingerprint($currentUser->password)
                )
            ) {
                $query->delete();

                return 'expired';
            }

            $expectedHash = $this->otpDigest($user, $otp);

            if (! hash_equals($pending->otp_hash, $expectedHash)) {
                $attempts = $pending->attempts + 1;

                if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                    $query->delete();

                    return 'locked';
                }

                $query->update([
                    'attempts' => $attempts,
                    'updated_at' => now(),
                ]);

                return 'invalid';
            }

            $currentUser->forceFill([
                'password' => $pending->pending_password_hash,
            ])->save();

            $query->delete();

            return 'success';
        });

        if ($status !== 'success') {
            $message = match ($status) {
                'locked' => __('message.profile.otp_locked'),
                'expired' => __('message.profile.otp_expired'),
                default => __('message.profile.otp_invalid'),
            };

            throw ValidationException::withMessages([
                'otp' => $message,
            ]);
        }
    }

    private function sendCode(
        User $user,
        string $pendingPasswordHash,
        string $passwordSnapshot
    ): void {
        $limitKey = "password-otp:send:{$user->id}";
        $cooldownKey = "password-otp:cooldown:{$user->id}";

        if (RateLimiter::tooManyAttempts(
            $limitKey,
            self::MAX_EMAILS_PER_HOUR
        )) {
            throw ValidationException::withMessages([
                'otp' => __('message.profile.otp_send_limit'),
            ]);
        }

        if (! Cache::add(
            $cooldownKey,
            true,
            self::SEND_COOLDOWN_SECONDS
        )) {
            throw ValidationException::withMessages([
                'otp' => __('message.profile.otp_cooldown'),
            ]);
        }

        // Tính cả lần gửi thất bại để hạn chế spam SMTP.
        RateLimiter::hit($limitKey, 3600);

        $otp = sprintf('%06d', random_int(0, 999999));
        $otpHash = $this->otpDigest($user, $otp);
        $now = now();

        try {
            DB::table('password_change_requests')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'otp_hash' => $otpHash,
                    'pending_password_hash' => $pendingPasswordHash,
                    'password_snapshot' => $passwordSnapshot,
                    'attempts' => 0,
                    'expires_at' => $now->copy()->addMinutes(
                        self::OTP_LIFETIME_MINUTES
                    ),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            Mail::to($user->email)->send(
                new PasswordChangeOtpMail($otp)
            );
        } catch (Throwable) {
            // Không giữ lại yêu cầu nếu không gửi được OTP.
            DB::table('password_change_requests')
                ->where('user_id', $user->id)
                ->where('otp_hash', $otpHash)
                ->delete();

            throw ValidationException::withMessages([
                'otp' => __('message.profile.otp_send_failed'),
            ]);
        }
    }

    private function withUserLock(User $user, callable $callback): void
    {
        try {
            Cache::lock("password-change:{$user->id}", 120)
                ->block(5, $callback);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'otp' => __('message.profile.otp_busy'),
            ]);
        }
    }

    private function otpDigest(User $user, string $otp): string
    {
        return hash_hmac(
            'sha256',
            "{$user->id}:{$otp}",
            (string) config('app.key')
        );
    }

    private function fingerprint(string $passwordHash): string
    {
        return hash_hmac(
            'sha256',
            $passwordHash,
            (string) config('app.key')
        );
    }
}
