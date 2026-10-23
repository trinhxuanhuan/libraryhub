<?php

namespace Tests\Unit;

use App\Services\Crypto\LearningBcrypt;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LearningBcryptTest extends TestCase
{
    public function test_educational_hash_matches_platform_bcrypt_with_fixed_salt(): void
    {
        $salt = hex2bin('00112233445566778899aabbccddeeff');
        $engine = new LearningBcrypt;

        foreach (['', 'password', '12345678', 'Tiếng Việt', str_repeat('a', 73)] as $password) {
            $computed = $engine->hash($password, 4, $salt);
            self::assertSame(60, strlen($computed));
            self::assertSame(crypt($password, substr($computed, 0, 29)), $computed);
            self::assertTrue($engine->verify($password, $computed));
            self::assertFalse($engine->verify('!'.$password, $computed));
        }
    }

    public function test_educational_hash_uses_random_salt_by_default(): void
    {
        $engine = new LearningBcrypt;
        $one = $engine->hash('password');
        $two = $engine->hash('password');

        self::assertNotSame($one, $two);
        self::assertTrue(password_verify('password', $one));
        self::assertTrue(password_verify('password', $two));
    }

    public function test_invalid_salt_is_rejected(): void
    {
        $engine = new LearningBcrypt;

        $this->expectException(InvalidArgumentException::class);
        $engine->hash('password', 4, 'too short');
    }
}
