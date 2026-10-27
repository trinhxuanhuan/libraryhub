<?php

namespace App\Enums;

enum BookCopyStatus: string
{
    case Available = 'available';
    case Borrowed = 'borrowed';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Maintenance = 'maintenance';
    case Unverified = 'unverified';
}
