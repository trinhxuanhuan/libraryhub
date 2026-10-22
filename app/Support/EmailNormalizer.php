<?php

declare(strict_types=1);

namespace App\Support;

class EmailNormalizer
{
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}
