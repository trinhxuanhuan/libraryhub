<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class ManualAuthenticator
{
    private const SESSION_KEY = 'manual_auth_user_id';

    // Xác minh email và mật khẩu
    public function verifyCredentials(
        string $email,
        string $password
    ): ?User {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return null;
        }

        $isCorrectPassword = password_verify(
            $password,
            $user->password
        );

        if (! $isCorrectPassword) {
            return null;
        }

        if (! $user->is_active) {
            return null;
        }

        return $user;
    }

    // Đăng nhập và tạo phiên đăng nhập
    public function login(
        Request $request,
        string $email,
        string $password
    ): bool {
        $user = $this->verifyCredentials($email, $password);

        if ($user === null) {
            return false;
        }

        $request->session()->regenerate();

        $request->session()->put(
            self::SESSION_KEY,
            $user->id
        );

        return true;
    }

    // Xác định người dùng đang đăng nhập
    public function user(Request $request): ?User
    {
        $userId = $request->session()->get(self::SESSION_KEY);

        if (! is_int($userId)) {
            return null;
        }

        $user = User::find($userId);

        if ($user === null || ! $user->is_active) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $user;
    }

    // Kiểm tra người dùng đã đăng nhập chưa
    public function check(Request $request): bool
    {
        return $this->user($request) !== null;
    }

    // Đăng xuất và hủy phiên đăng nhập
    public function logout(Request $request): void
    {
        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }
}
