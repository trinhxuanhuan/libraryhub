<?php

namespace App\Services\Crypto;

use InvalidArgumentException;

/**
 * Reference/teaching implementation of bcrypt $2y$ (pure PHP rounds).
 * DO NOT replace a platform password hasher with this in a real login system.
 * This is intentionally a separate educational component.
 */
final class LearningBcrypt
{
    private const ALPHABET = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    /** @var list<int> */
    private array $p = [];

    /** @var list<int> */
    private array $s = [];

    /**
     * Compute full bcrypt string from password, cost, and optional 16-byte salt.
     * Optional fixed salt exists ONLY for repeatable test vectors.
     */
    public function hash(string $password, int $cost = 4, ?string $salt = null): string
    {
        if ($cost < 4 || $cost > 12) {
            throw new InvalidArgumentException('Learning implementation accepts cost from 4 to 12.');
        }

        if (str_contains($password, "\0")) {
            throw new InvalidArgumentException('Null bytes in password are not supported.');
        }

        $salt ??= random_bytes(16);

        if (strlen($salt) !== 16) {
            throw new InvalidArgumentException('Salt must be exactly 16 bytes.');
        }

        // bcrypt $2y$: the C-style trailing NUL participates in the key,
        // but only the first 72 input bytes can enter the key schedule.
        $key = substr($password."\0", 0, 72);
        $this->p = BlowfishConstants::P;
        $this->s = BlowfishConstants::S;

        $this->expandKey($salt, $key);

        for ($i = 0, $iterations = 1 << $cost; $i < $iterations; $i++) {
            $this->expandKey('', $key);
            $this->expandKey('', $salt);
        }

        // Three 64-bit blocks, ASCII 'OrpheanBeholderScryDoubt'.
        $words = [
            0x4F727068, 0x65616E42, 0x65686F6C,
            0x64657253, 0x63727944, 0x6F756274,
        ];

        for ($i = 0; $i < 64; $i++) {
            for ($j = 0; $j < 6; $j += 2) {
                [$words[$j], $words[$j + 1]] = $this->encipher(
                    $words[$j], $words[$j + 1]
                );
            }
        }

        $bytes = pack('N*', ...$words);
        // bcrypt standard uses the first 23 of the 24 encrypted bytes.
        $digest = self::encodeBcrypt64(substr($bytes, 0, 23));
        $encodedSalt = self::encodeBcrypt64($salt);

        return sprintf('$2y$%02d$%s%s', $cost, $encodedSalt, $digest);
    }

    public function verify(string $password, string $hash): bool
    {
        if (! preg_match('/^\$2y\$(\d{2})\$([.\/A-Za-z0-9]{53})$/D', $hash, $matches)) {
            return false;
        }

        $cost = (int) $matches[1];
        if ($cost < 4 || $cost > 12) {
            return false;
        }

        $salt = self::decodeBcrypt64(substr($matches[2], 0, 22), 16);
        if (strlen($salt) !== 16) {
            return false;
        }

        try {
            $computed = $this->hash($password, $cost, $salt);
        } catch (InvalidArgumentException) {
            return false;
        }

        return hash_equals($hash, $computed);
    }

    /** ExpandKey(salt, key): updates all P and S values. */
    private function expandKey(string $salt, string $key): void
    {
        $keyOffset = 0;
        for ($i = 0; $i < 18; $i++) {
            $this->p[$i] ^= self::streamWord($key, $keyOffset);
        }

        $saltOffset = 0;
        $left = 0;
        $right = 0;

        for ($i = 0; $i < 18; $i += 2) {
            if ($salt !== '') {
                $left ^= self::streamWord($salt, $saltOffset);
                $right ^= self::streamWord($salt, $saltOffset);
            }

            [$left, $right] = $this->encipher($left, $right);
            $this->p[$i] = $left;
            $this->p[$i + 1] = $right;
        }

        for ($i = 0; $i < 1024; $i += 2) {
            if ($salt !== '') {
                $left ^= self::streamWord($salt, $saltOffset);
                $right ^= self::streamWord($salt, $saltOffset);
            }

            [$left, $right] = $this->encipher($left, $right);
            $this->s[$i] = $left;
            $this->s[$i + 1] = $right;
        }
    }

    /** Convert 4 cyclic key bytes to a big-endian unsigned 32-bit word. */
    private static function streamWord(string $data, int &$offset): int
    {
        $length = strlen($data);
        if ($length === 0) {
            throw new InvalidArgumentException('Empty key is invalid.');
        }

        $word = 0;
        for ($i = 0; $i < 4; $i++) {
            $word = (($word << 8) | ord($data[$offset])) & 0xFFFFFFFF;
            $offset = ($offset + 1) % $length;
        }

        return $word;
    }

    /** Blowfish F, based on four 256-element S-boxes. */
    private function f(int $x): int
    {
        $a = ($x >> 24) & 0xFF;
        $b = ($x >> 16) & 0xFF;
        $c = ($x >> 8) & 0xFF;
        $d = $x & 0xFF;

        $sum = ($this->s[$a] + $this->s[256 + $b]) & 0xFFFFFFFF;
        $xor = $sum ^ $this->s[512 + $c];

        return ($xor + $this->s[768 + $d]) & 0xFFFFFFFF;
    }

    /** 16-round Blowfish Feistel encipher of one 64-bit block. */
    private function encipher(int $left, int $right): array
    {
        for ($i = 0; $i < 16; $i++) {
            $left = ($left ^ $this->p[$i]) & 0xFFFFFFFF;
            $right = ($right ^ $this->f($left)) & 0xFFFFFFFF;
            [$left, $right] = [$right, $left];
        }

        [$left, $right] = [$right, $left];
        $right = ($right ^ $this->p[16]) & 0xFFFFFFFF;
        $left = ($left ^ $this->p[17]) & 0xFFFFFFFF;

        return [$left, $right];
    }

    /** bcrypt-specific base64 alphabet and packing, no '=' padding. */
    private static function encodeBcrypt64(string $data): string
    {
        $result = '';
        $length = strlen($data);
        $offset = 0;

        while ($offset < $length) {
            $c1 = ord($data[$offset++]);
            $result .= self::ALPHABET[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;

            if ($offset >= $length) {
                $result .= self::ALPHABET[$c1];
                break;
            }

            $c2 = ord($data[$offset++]);
            $c1 |= ($c2 >> 4) & 0x0F;
            $result .= self::ALPHABET[$c1];
            $c1 = ($c2 & 0x0F) << 2;

            if ($offset >= $length) {
                $result .= self::ALPHABET[$c1];
                break;
            }

            $c2 = ord($data[$offset++]);
            $c1 |= ($c2 >> 6) & 0x03;
            $result .= self::ALPHABET[$c1];
            $result .= self::ALPHABET[$c2 & 0x3F];
        }

        return $result;
    }

    private static function decodeBcrypt64(string $value, int $outputBytes): string
    {
        $buffer = 0;
        $bitCount = 0;
        $result = '';

        foreach (str_split($value) as $char) {
            $position = strpos(self::ALPHABET, $char);
            if ($position === false) {
                return '';
            }

            $buffer = ($buffer << 6) | $position;
            $bitCount += 6;

            if ($bitCount >= 8) {
                $bitCount -= 8;
                $result .= chr(($buffer >> $bitCount) & 0xFF);
                $buffer &= (1 << $bitCount) - 1;
                if (strlen($result) === $outputBytes) {
                    break;
                }
            }
        }

        return $result;
    }
}
