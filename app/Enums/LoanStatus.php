<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Pending = 'pending';
    case Borrowed = 'borrowed';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
