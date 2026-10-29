<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_register_with_valid_phone(): void
    {
        $this->post(route('register.store'), $this->validData())
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $user = User::where('email', 'member@example.com')->firstOrFail();

        $this->assertSame('Nguyễn Văn A', $user->name);
        $this->assertSame('0912345678', $user->phone);
        $this->assertSame(UserRole::Member, $user->role);

        $this->assertTrue(
            app(PasswordHasher::class)->verify(
                'Password123',
                $user->password
            )
        );
    }

    public function test_phone_is_required(): void
    {
        $data = $this->validData();
        unset($data['phone']);

        $this->post(route('register.store'), $data)
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        foreach (['12345', '0123456789', 'abcdefghij', '09123456789'] as $phone) {
            $this->post(route('register.store'), [
                ...$this->validData(),
                'phone' => $phone,
            ])->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
        ]);

        $this->post(route('register.store'), $this->validData())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post(route('register.store'), [
            ...$this->validData(),
            'password_confirmation' => 'DifferentPassword123',
        ])->assertSessionHasErrors('password_confirmation');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_member_cannot_assign_privileged_fields(): void
    {
        $this->post(route('register.store'), [
            ...$this->validData(),
            'role' => 'admin',
            'is_active' => false,
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'member@example.com')->firstOrFail();

        $this->assertSame(UserRole::Member, $user->role);
        $this->assertTrue($user->is_active);
    }

    private function validData(): array
    {
        return [
            'name' => 'Nguyễn Văn A',
            'email' => 'member@example.com',
            'phone' => '0912345678',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];
    }
}
