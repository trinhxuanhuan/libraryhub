<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: int
{
    case Admin = 1;
    case Librarian = 2;
    case Member = 3;

    public function key(): string
    {
        return match ($this) {
            self::Admin => 'admin',
            self::Librarian => 'librarian',
            self::Member => 'member',
        };
    }
}
