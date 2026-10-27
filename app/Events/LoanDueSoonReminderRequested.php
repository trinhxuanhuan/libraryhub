<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LoanDueSoonReminderRequested
{
    use Dispatchable;

    public function __construct(
        public readonly int $loanId
    ) {}
}
