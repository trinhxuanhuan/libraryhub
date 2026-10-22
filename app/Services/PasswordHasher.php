<?php

namespace App\Services;

class PasswordHasher
{
    public function hash(string $password): string
    {
        return password_hash(
            $password,
            PASSWORD_BCRYPT,
            ['cost' => (int) config('hashing.bcrypt.rounds', 12)]
        );
    }

    public function verify(string $password, string $hashedPassword): bool {
        return password_verify(
            $password,
            $hashedPassword
        );
    }
}
