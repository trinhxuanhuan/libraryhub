<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'TestPassword123!';

    // Tạo User dùng riêng cho kiểm thử
    private function createTestUser(
        UserRole $role = UserRole::Member,
        bool $isActive = true
    ): User {
        $user = User::factory()->create([
            'password' => password_hash(
                self::PASSWORD,
                PASSWORD_BCRYPT,
                ['cost' => (int) config('hashing.bcrypt.rounds', 12)]
            ),
        ]);

        $user->forceFill([
            'role' => $role,
            'is_active' => $isActive,
        ])->save();

        return $user;
    }

    // Đăng nhập đúng thông tin
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createTestUser();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('home'));

        $response->assertSessionHas(
            'manual_auth_user_id',
            $user->id
        );
    }

    // Đăng nhập sai mật khẩu
    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = $this->createTestUser();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors('email');

        $response->assertSessionMissing('manual_auth_user_id');
    }

    // Tài khoản bị khóa không được đăng nhập
    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->createTestUser(
            UserRole::Member,
            false
        );

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertSessionHasErrors('email');

        $response->assertSessionMissing('manual_auth_user_id');
    }

    // Khách chưa đăng nhập không thể vào trang Admin
    public function test_guest_cannot_access_admin(): void
    {
        $response = $this->get(route('admin.books.index'));

        $response->assertRedirect(route('login'));
    }

    // Admin được phép truy cập
    public function test_admin_can_access_admin_books(): void
    {
        $admin = $this->createTestUser(UserRole::Admin);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ]);

        $response = $this->get(route('admin.books.index'));

        $response->assertOk();
    }

    // Member không được vào trang Admin
    public function test_member_cannot_access_admin_books(): void
    {
        $member = $this->createTestUser(UserRole::Member);

        $this->post(route('login.store'), [
            'email' => $member->email,
            'password' => self::PASSWORD,
        ]);

        $response = $this->get(route('admin.books.index'));

        $response->assertForbidden();
    }

    // Đăng xuất phải hủy Session đăng nhập
    public function test_user_can_logout(): void
    {
        $user = $this->createTestUser();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('home'));

        $response->assertSessionMissing('manual_auth_user_id');

        $this->get(route('admin.books.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_email_in_different_case(): void
    {
        $password = 'Password123!';

        $user = User::factory()->create([
            'email' => 'demo@example.com',
            'password' => password_hash(
                $password,
                PASSWORD_BCRYPT,
                ['cost' => (int) config('hashing.bcrypt.rounds', 12)]
            ),
        ]);

        $this->post('/login', [
            'email' => '  Demo@EXAMPLE.COM  ',
            'password' => $password,
        ])->assertSessionHas('manual_auth_user_id', $user->id);
    }
}
