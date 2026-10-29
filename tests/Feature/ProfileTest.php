<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile(): void
    {
        $this->get(route('profile.show'))
            ->assertRedirect(route('login'));

        $this->patch(route('profile.update'), [
            'name' => 'New Name',
            'phone' => '0912345678',
        ])->assertRedirect(route('login'));
    }

    public function test_all_roles_can_view_their_profile(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = $this->loginAsUser($role);

            $this->get(route('profile.show'))
                ->assertOk()
                ->assertSeeText($user->name)
                ->assertSee('value="'.$user->email.'"', false);
        }
    }

    public function test_user_can_update_name_and_phone(): void
    {
        $user = $this->loginAsUser();

        $this->patch(route('profile.update'), [
            'name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
        ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Nguyễn Văn A', $user->name);
        $this->assertSame('0912345678', $user->phone);
    }

    public function test_name_and_phone_are_required(): void
    {
        $user = $this->loginAsUser();
        $originalName = $user->name;

        $this->patch(route('profile.update'), [
            'name' => '',
            'phone' => '',
        ])->assertSessionHasErrors(['name', 'phone']);

        $this->assertSame($originalName, $user->fresh()->name);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $user = $this->loginAsUser();
        $originalName = $user->name;

        $this->patch(route('profile.update'), [
            'name' => 'Tên thay đổi',
            'phone' => '12345',
        ])->assertSessionHasErrors('phone');

        $this->assertSame($originalName, $user->fresh()->name);
    }

    public function test_user_cannot_update_sensitive_fields(): void
    {
        $user = $this->loginAsUser();

        $originalEmail = $user->email;
        $originalPassword = $user->password;

        $this->patch(route('profile.update'), [
            'name' => 'Tên mới',
            'phone' => '0987654321',
            'email' => 'hacked@example.com',
            'role' => 'admin',
            'is_active' => false,
            'password' => 'HackedPassword123',
        ])->assertRedirect(route('profile.show'));

        $user->refresh();

        $this->assertSame('Tên mới', $user->name);
        $this->assertSame('0987654321', $user->phone);
        $this->assertSame($originalEmail, $user->email);
        $this->assertSame(UserRole::Member, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_updating_profile_does_not_change_another_user(): void
    {
        $user = $this->loginAsUser();
        $otherUser = User::factory()->create();

        $originalName = $otherUser->name;

        $this->patch(route('profile.update'), [
            'name' => 'Tên đã cập nhật',
            'phone' => '0912345678',
        ])->assertRedirect(route('profile.show'));

        $this->assertSame(
            'Tên đã cập nhật',
            $user->fresh()->name
        );

        $this->assertSame(
            $originalName,
            $otherUser->fresh()->name
        );
    }

    private function loginAsUser(
        UserRole $role = UserRole::Member
    ): User {
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);

        $this->withSession([
            'manual_auth_user_id' => $user->id,
        ]);

        return $user;
    }
}
