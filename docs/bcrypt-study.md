# LibraryHub: bcrypt educational engine (pure PHP)

These files are intended to be copied into the **existing feature/auth-manual branch**.
They implement bcrypt rounds and format in PHP **without calling `password_hash()`,
`password_verify()` or `crypt()` in the hashing algorithm**. The PHPUnit tests
use `crypt()` and `password_verify()` as an external reference to compare.

**Not production-ready cryptography.** Do not replace the existing
`App\Services\PasswordHasher` on login/register with this implementation.
This is deliberately named `LearningBcrypt` to keep it separate.

## Installation (from inside ~/PHP-Learning/libraryhub)

```bash
# Copy this archive into your LibraryHub project root, then:
unzip -n libraryhub-bcrypt-study.zip
./vendor/bin/pint app/Services/Crypto/ app/Console/Commands/StudyBcryptCommand.php tests/Unit/LearningBcryptTest.php
php artisan bcrypt:study --cost=4
php artisan test --filter=LearningBcryptTest
php artisan test --filter=ManualAuthenticationTest
```

Never use real passwords for the educational command; it operates on an
internal dummy string. Costs >4 are much slower in pure PHP.


## How the engine works

1. `BlowfishConstants`: initial P-array (18 words) and S-boxes (1024 words), taken
   from fractional hex digits of pi according to Blowfish.
2. `LearningBcrypt::expandKey`: mixes password and 16-byte salt into P and S state.
3. `1 << $cost` iterations: repeats key expansion with password and salt.
4. `encipher`: Blowfish 16-round Feistel transform of a 64-bit block.
5. Encrypt `OrpheanBeholderScryDoubt` (3 blocks) 64 times.
6. Encode salt (22 chars) and first 23 digest bytes (31 chars) using bcrypt base64,
   prefixing `$2y$<cost>$` to make a 60-character hash.
7. `verify` extracts existing salt/cost and recomputes, comparing with `hash_equals`.

Educational limits: bcrypt `$2y$` only, cost 4..12, null bytes in password
rejected. This limits test duration and avoids presenting this as a drop-in
platform password library.

Reference: https://www.openbsd.org/papers/bcrypt-paper.pdf
